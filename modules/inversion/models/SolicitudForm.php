<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;
use yii\web\UploadedFile;

final class SolicitudForm extends Model
{
    public const DOCUMENT_ATTRIBUTES = [
        'peticionFormal',
        'rucDocumento',
        'cedulaDocumento',
        'proyectoDocumento',
        'certificadoNoAdeudar',
    ];

    public const OBRA_DOCUMENT_ATTRIBUTES = ['cronogramaObra', 'planosObra'];

    public string $razonSocial = '';
    public string $tipoPersona = '';
    public string $ruc = '';
    public string $email = '';
    public string $telefono = '';
    public string $direccionInversionista = '';
    public string $domicilioTributario = '';
    public string $nombreProyecto = '';
    public string $descripcionProyecto = '';
    public string $ubicacion = '';
    public string $parroquia = '';
    public string $tipoFuente = '';
    public string $entidadFinanciera = '';
    public string $fuentesFinanciamiento = '';
    public string $detalleInversion = '';
    public string $montoInversion = '';
    public string $cargoEmpleo = '';
    public string $tipoContrato = '';
    public string $empleosProyectados = '';
    public string $empleosJovenesProyectados = '';
    public bool $incluyeObra = false;
    public bool $declaracionAntilavado = false;
    public bool $documentacionFirmada = false;

    public ?UploadedFile $peticionFormal = null;
    public ?UploadedFile $rucDocumento = null;
    public ?UploadedFile $cedulaDocumento = null;
    public ?UploadedFile $proyectoDocumento = null;
    public ?UploadedFile $cronogramaObra = null;
    public ?UploadedFile $planosObra = null;
    public ?UploadedFile $certificadoNoAdeudar = null;

    public function rules(): array
    {
        return [
            [[
                'razonSocial',
                'tipoPersona',
                'ruc',
                'email',
                'telefono',
                'direccionInversionista',
                'domicilioTributario',
                'nombreProyecto',
                'descripcionProyecto',
                'ubicacion',
                'parroquia',
                'tipoFuente',
                'entidadFinanciera',
                'fuentesFinanciamiento',
                'detalleInversion',
                'montoInversion',
                'cargoEmpleo',
                'tipoContrato',
                'empleosProyectados',
                'empleosJovenesProyectados',
            ], 'trim'],
            [[
                'razonSocial',
                'ruc',
                'email',
                'telefono',
                'descripcionProyecto',
                'ubicacion',
                'fuentesFinanciamiento',
                'detalleInversion',
                'montoInversion',
                'empleosProyectados',
                'empleosJovenesProyectados',
                ...self::DOCUMENT_ATTRIBUTES,
            ], 'required'],
            ['ruc', 'match', 'pattern' => '/^\d{13}$/', 'message' => 'El RUC debe contener exactamente 13 dígitos.'],
            ['email', 'email'],
            ['tipoPersona', 'in', 'range' => ['Natural', 'Jurídica']],
            ['tipoFuente', 'in', 'range' => ['Recursos propios', 'Crédito bancario', 'Inversionista', 'Financiamiento público', 'Otro']],
            ['tipoContrato', 'in', 'range' => ['Indefinido', 'Temporal', 'Servicios profesionales', 'Otro']],
            ['telefono', 'match', 'pattern' => '/^\+?[0-9 ()-]{7,20}$/', 'message' => 'Ingrese un teléfono válido.'],
            ['montoInversion', 'number', 'min' => 0.01],
            [['empleosProyectados', 'empleosJovenesProyectados'], 'integer', 'min' => 0],
            [
                'empleosJovenesProyectados',
                'compare',
                'compareAttribute' => 'empleosProyectados',
                'operator' => '<=',
                'type' => 'number',
                'message' => 'El empleo joven no puede superar el total de empleos proyectados.',
            ],
            [['descripcionProyecto', 'fuentesFinanciamiento'], 'string', 'max' => 2000],
            [['razonSocial', 'nombreProyecto'], 'string', 'max' => 180],
            [['direccionInversionista', 'domicilioTributario', 'ubicacion', 'entidadFinanciera', 'detalleInversion'], 'string', 'max' => 255],
            [['parroquia', 'cargoEmpleo'], 'string', 'max' => 150],
            [['incluyeObra', 'declaracionAntilavado', 'documentacionFirmada'], 'boolean'],
            [['declaracionAntilavado', 'documentacionFirmada'], 'compare', 'compareValue' => true, 'type' => 'boolean'],
            [
                [...self::DOCUMENT_ATTRIBUTES, ...self::OBRA_DOCUMENT_ATTRIBUTES],
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['pdf'],
                'checkExtensionByMimeType' => true,
                'mimeTypes' => ['application/pdf'],
                'maxSize' => 10 * 1024 * 1024,
                'tooBig' => 'Cada archivo debe pesar máximo 10 MB.',
            ],
            [
                self::OBRA_DOCUMENT_ATTRIBUTES,
                'required',
                'when' => fn (self $model): bool => $model->incluyeObra,
                'whenClient' => "function () { return document.querySelector('[name=\"SolicitudForm[incluyeObra]\"]:checked')?.value === '1'; }",
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'razonSocial' => 'Nombre o razón social',
            'tipoPersona' => 'Tipo de persona',
            'ruc' => 'RUC',
            'email' => 'Correo electrónico',
            'telefono' => 'Teléfono',
            'direccionInversionista' => 'Dirección del inversionista',
            'domicilioTributario' => 'Domicilio tributario en Montecristi',
            'nombreProyecto' => 'Nombre del proyecto',
            'descripcionProyecto' => 'Descripción general',
            'ubicacion' => 'Localización geográfica',
            'parroquia' => 'Parroquia',
            'tipoFuente' => 'Tipo de fuente',
            'entidadFinanciera' => 'Entidad de financiamiento',
            'fuentesFinanciamiento' => 'Fuentes de financiamiento',
            'detalleInversion' => 'Monto total y detalle de la inversión',
            'montoInversion' => 'Monto total (USD)',
            'empleosProyectados' => 'Empleos proyectados',
            'empleosJovenesProyectados' => 'Empleos jóvenes proyectados',
            'cargoEmpleo' => 'Cargo o perfil principal',
            'tipoContrato' => 'Tipo de contrato',
            'incluyeObra' => '¿El proyecto contempla ejecución de obras?',
            'peticionFormal' => 'Petición formal dirigida a la Alcaldía',
            'rucDocumento' => 'RUC con domicilio o establecimiento en Montecristi',
            'cedulaDocumento' => 'Copia de cédula del inversionista',
            'proyectoDocumento' => 'Proyecto de inversión firmado',
            'cronogramaObra' => 'Cronograma valorado de ejecución',
            'planosObra' => 'Planos de obra',
            'certificadoNoAdeudar' => 'Certificado de no adeudar al GADM Montecristi',
            'declaracionAntilavado' => 'Declaro no estar involucrado/a en procesos de lavado de activos',
            'documentacionFirmada' => 'Confirmo que la documentación está debidamente firmada',
        ];
    }

    public function loadUploadedFiles(): void
    {
        foreach ([...self::DOCUMENT_ATTRIBUTES, ...self::OBRA_DOCUMENT_ATTRIBUTES] as $attribute) {
            $this->{$attribute} = UploadedFile::getInstance($this, $attribute);
        }
    }
}
