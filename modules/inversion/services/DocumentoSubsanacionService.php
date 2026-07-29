<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use app\modules\inversion\models\SubsanacionForm;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Connection;

final class DocumentoSubsanacionService
{
    public function __construct(private readonly ?Connection $connection = null)
    {
    }

    /**
     * @param array<string, mixed> $record
     */
    public function replace(SubsanacionForm $form, array $record): void
    {
        $db = $this->connection ?? Yii::$app->db;
        $currentPath = realpath((string) $record['ruta_archivo']);
        $storageRoot = realpath(Yii::getAlias('@runtime/solicitudes'));
        if (
            $currentPath === false
            || $storageRoot === false
            || !str_starts_with($currentPath, $storageRoot . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('El documento original no está disponible.');
        }

        $documentId = (int) $record['documento_id'];
        $lastVersion = (int) (new \yii\db\Query())
            ->from('documento_version')
            ->where(['documento_id' => $documentId])
            ->max('version', $db);
        $currentVersion = max(1, $lastVersion);
        $newVersion = $currentVersion + 1;
        $newPath = dirname($currentPath) . DIRECTORY_SEPARATOR
            . 'documento-' . $documentId . '-v' . $newVersion . '.pdf';

        if ($form->documento === null || !$form->documento->saveAs($newPath)) {
            throw new RuntimeException('No fue posible guardar la nueva versión.');
        }

        $transaction = $db->beginTransaction();
        try {
            if ($lastVersion === 0) {
                $db->createCommand()->insert('documento_version', [
                    'documento_id' => $documentId,
                    'version' => 1,
                    'nombre_archivo' => $record['nombre_archivo'],
                    'ruta_archivo' => $currentPath,
                    'subido_por' => 'Presentación inicial',
                    'fecha_subida' => $record['fecha_subida'],
                ])->execute();
            }

            $db->createCommand()->insert('documento_version', [
                'documento_id' => $documentId,
                'version' => $newVersion,
                'nombre_archivo' => $form->documento->name,
                'ruta_archivo' => $newPath,
                'subido_por' => 'Inversionista',
            ])->execute();
            $db->createCommand()->update('documento', [
                'nombre_archivo' => $form->documento->name,
                'ruta_archivo' => $newPath,
                'fecha_subida' => date('Y-m-d H:i:s'),
            ], ['id' => $documentId])->execute();
            $db->createCommand()->update('observacion_documento', [
                'estado' => 'Subsanada',
                'fecha_subsanacion' => date('Y-m-d H:i:s'),
            ], ['id' => $record['observacion_id']])->execute();
            $pendingCount = (new \yii\db\Query())
                ->from(['o' => 'observacion_documento'])
                ->innerJoin(['d' => 'documento'], 'd.id = o.documento_id')
                ->where([
                    'd.proyecto_id' => $record['proyecto_id'],
                    'o.estado' => 'Pendiente',
                ])
                ->count('*', $db);
            $db->createCommand()->update('proyecto', [
                'estado' => (int) $pendingCount > 0 ? 'Subsanación' : 'En revisión',
            ], ['id' => $record['proyecto_id']])->execute();
            (new ProyectoHistory($db))->record(
                (int) $record['proyecto_id'],
                'Inversionista',
                'Documento subsanado',
                'Subsanación',
                (int) $pendingCount > 0 ? 'Subsanación' : 'En revisión',
                'Se cargó la versión ' . $newVersion . ' del documento.',
            );
            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            if (is_file($newPath)) {
                unlink($newPath);
            }
            throw $exception;
        }

        (new ProyectoNotifier($db))->notify(
            (int) $record['proyecto_id'],
            (string) ($record['correo'] ?? ''),
            'Documento corregido recibido',
            'La nueva versión del documento fue recibida correctamente.',
        );
        (new ProyectoNotifier($db))->notify(
            (int) $record['proyecto_id'],
            (string) Yii::$app->params['adminEmail'],
            'Expediente con documento subsanado',
            'El inversionista cargó una nueva versión documental para revisión.',
        );
    }
}
