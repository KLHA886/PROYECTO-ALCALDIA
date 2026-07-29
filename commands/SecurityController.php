<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

final class SecurityController extends Controller
{
    /**
     * Reports expired rejected projects. Deletion requires --apply=1.
     */
    public function actionRetention(int $apply = 0): int
    {
        $days = (int) Yii::$app->params['documentRetentionDays'];
        $cutoff = date('Y-m-d', strtotime('-' . $days . ' days'));
        $ids = (new Query())
            ->select('id')
            ->from('proyecto')
            ->where(['estado' => 'Rechazado'])
            ->andWhere(['<', 'fecha_presentacion', $cutoff])
            ->column();

        $this->stdout(count($ids) . " expediente(s) cumplen la política de retención.\n");
        if ($apply !== 1 || $ids === []) {
            $this->stdout("Simulación: no se eliminó información. Use --apply=1 tras revisar el reporte.\n");
            return ExitCode::OK;
        }

        $this->stderr(
            "La eliminación automática permanece deshabilitada: los archivos requieren archivo institucional previo.\n",
        );
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
