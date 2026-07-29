<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use app\modules\inversion\models\SolicitudForm;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Connection;
use yii\helpers\FileHelper;

final class SolicitudStorage
{
    private const DOCUMENT_TYPES = [
        'peticionFormal' => 'Petición formal',
        'rucDocumento' => 'RUC',
        'cedulaDocumento' => 'Copia de cédula',
        'proyectoDocumento' => 'Otro',
        'cronogramaObra' => 'Otro',
        'planosObra' => 'Plano de obra',
        'certificadoNoAdeudar' => 'Certificado no adeudar',
    ];

    public function __construct(private readonly ?Connection $connection = null)
    {
    }

    public function save(SolicitudForm $form): string
    {
        $db = $this->connection ?? Yii::$app->db;
        $temporaryCode = 'TMP-' . bin2hex(random_bytes(8));
        $directory = Yii::getAlias('@runtime/solicitudes/' . $temporaryCode);
        FileHelper::createDirectory($directory, 0770);
        $finalDirectory = null;
        $transaction = $db->beginTransaction();
        $projectId = 0;
        $code = '';

        try {
            $documents = $this->saveDocuments($form, $directory);
            $projectId = $this->saveDatabaseRecords($db, $form, $documents, $directory);
            $code = sprintf('INV-%s-%06d', date('Ymd'), $projectId);
            $finalDirectory = Yii::getAlias('@runtime/solicitudes/' . $code);

            if (!rename($directory, $finalDirectory)) {
                throw new RuntimeException('No fue posible finalizar el expediente documental.');
            }

            $db->createCommand()->update(
                'documento',
                ['ruta_archivo' => new \yii\db\Expression(
                    'REPLACE([[ruta_archivo]], :temporary, :final)',
                    [':temporary' => $temporaryCode, ':final' => $code],
                )],
                ['proyecto_id' => $projectId],
            )->execute();
            (new ProyectoHistory($db))->record(
                $projectId,
                'Inversionista',
                'Solicitud presentada',
                null,
                'Presentado',
                'Expediente registrado con código ' . $code,
            );
            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            if (is_dir($directory)) {
                FileHelper::removeDirectory($directory);
            }
            if ($finalDirectory !== null && is_dir($finalDirectory)) {
                FileHelper::removeDirectory($finalDirectory);
            }
            throw $exception;
        }

        (new ProyectoNotifier($db))->notify(
            $projectId,
            $form->email,
            'Solicitud de inversión registrada',
            'Su solicitud fue registrada correctamente. Código de seguimiento: ' . $code,
        );

        return $code;
    }

    /**
     * @return array<string, string>
     */
    private function saveDocuments(SolicitudForm $form, string $directory): array
    {
        $documents = [];
        foreach ([...SolicitudForm::DOCUMENT_ATTRIBUTES, ...SolicitudForm::OBRA_DOCUMENT_ATTRIBUTES] as $attribute) {
            $file = $form->{$attribute};
            if ($file === null) {
                continue;
            }

            $filename = $attribute . '.pdf';
            if (!$file->saveAs($directory . DIRECTORY_SEPARATOR . $filename)) {
                throw new RuntimeException('No fue posible guardar la documentación.');
            }
            $documents[$attribute] = $filename;
        }

        return $documents;
    }

    /**
     * @param array<string, string> $documents
     */
    private function saveDatabaseRecords(
        Connection $db,
        SolicitudForm $form,
        array $documents,
        string $directory,
    ): int {
        $investorId = $this->saveInvestor($db, $form);

        $db->createCommand()->insert('proyecto', [
            'inversionista_id' => $investorId,
            'nombre' => $form->nombreProyecto,
            'descripcion' => $form->descripcionProyecto,
            'provincia' => 'Manabí',
            'canton' => 'Montecristi',
            'parroquia' => $form->parroquia,
            'direccion_proyecto' => $form->ubicacion,
            'monto_total' => $form->montoInversion,
            'fecha_presentacion' => date('Y-m-d'),
            'estado' => 'Presentado',
            'requiere_uso_suelo' => 0,
            'requiere_obra' => (int) $form->incluyeObra,
        ])->execute();
        $projectId = (int) $db->getLastInsertID();

        $db->createCommand()->insert('fuente_financiamiento', [
            'proyecto_id' => $projectId,
            'tipo_fuente' => $form->tipoFuente,
            'entidad' => $form->entidadFinanciera,
            'descripcion' => $form->fuentesFinanciamiento,
            'monto' => $form->montoInversion,
        ])->execute();

        $db->createCommand()->insert('inversion_proyectada', [
            'proyecto_id' => $projectId,
            'descripcion' => $form->detalleInversion,
            'cantidad' => 1,
            'valor_unitario' => $form->montoInversion,
            'valor_total' => $form->montoInversion,
        ])->execute();

        $db->createCommand()->insert('empleo_proyectado', [
            'proyecto_id' => $projectId,
            'cargo' => $form->cargoEmpleo,
            'cantidad_personas' => $form->empleosProyectados,
            'cantidad_jovenes' => $form->empleosJovenesProyectados,
            'tipo_contrato' => $form->tipoContrato,
        ])->execute();

        foreach ($documents as $attribute => $filename) {
            $db->createCommand()->insert('documento', [
                'proyecto_id' => $projectId,
                'tipo_documento' => self::DOCUMENT_TYPES[$attribute],
                'nombre_archivo' => $form->{$attribute}?->name ?? $filename,
                'ruta_archivo' => $directory . DIRECTORY_SEPARATOR . $filename,
                'firmado' => (int) $form->documentacionFirmada,
            ])->execute();
        }

        return $projectId;
    }

    private function saveInvestor(Connection $db, SolicitudForm $form): int
    {
        $values = [
            'tipo_persona' => $form->tipoPersona,
            'nombres' => $form->razonSocial,
            'direccion' => $form->direccionInversionista,
            'telefono' => $form->telefono,
            'correo' => $form->email,
            'domicilio_tributario' => $form->domicilioTributario,
            'registrado_montecristi' => 1,
            'declaracion_licitud_fondos' => (int) $form->declaracionAntilavado,
        ];
        $investorId = $db->createCommand(
            'SELECT [[id]] FROM {{%inversionista}} WHERE [[identificacion]] = :identificacion',
            [':identificacion' => $form->ruc],
        )->queryScalar();

        if ($investorId !== false) {
            $db->createCommand()->update('inversionista', $values, ['id' => $investorId])->execute();
            return (int) $investorId;
        }

        $db->createCommand()->insert('inversionista', [
            ...$values,
            'identificacion' => $form->ruc,
        ])->execute();

        return (int) $db->getLastInsertID();
    }
}
