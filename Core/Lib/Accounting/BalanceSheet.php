<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\Accounting;

/**
 * Description of BalanceSheet
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Raul Jiménez <comercial@nazcanetworks.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class BalanceSheet extends AccountingBase
{

    /**
     * Date from for filter
     *
     * @var string
     */
    protected $dateFromPrev;

    /**
     * * Date to for filter
     *
     * @var string
     */
    protected $dateToPrev;

    /**
     * BalanceSheet constructor.
     *
     * @param string $dateFrom
     * @param string $dateTo
     */
    public function __construct($dateFrom, $dateTo)
    {
        parent::__construct($dateFrom, $dateTo);

        $this->dateFromPrev = $this->addToDate($this->dateFrom, '-1 year');
        $this->dateToPrev = $this->addToDate($this->dateTo, '-1 year');
    }

    /**
     * Generate the balance ammounts between two dates.
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $balanceSheet = new BalanceSheet($dateFrom, $dateTo);
        $data = $balanceSheet->getData();
        if (empty($data)) {
            return [];
        }
        return $balanceSheet->calcSheetBalance($data);
    }

    /**
     * Format de balance including then chapters
     *
     * @param array $balance
     *
     * @return array
     */
    private function calcSheetBalance($data)
    {
        $balanceCalculado = [];

        if (!empty($balance)) {
            foreach ($balance as $lineaBalance) {
                if (!array_key_exists($lineaBalance['naturaleza'], $balanceCalculado)) {
                    $balanceCalculado[$lineaBalance['naturaleza']] = [
                        'descripcion' => $lineaBalance['naturaleza'] = 'A' ? 'ACTIVO' : 'PASIVO',
                        'saldo' => $lineaBalance['saldo'], 'saldoPrev' => $lineaBalance['saldoPrev']
                    ];
                } else {
                    $balanceCalculado[$lineaBalance['naturaleza']]['saldo'] += $lineaBalance['saldo'];
                    $balanceCalculado[$lineaBalance['naturaleza']]['saldoPrev'] += $lineaBalance['saldoPrev'];
                }

                $this->processDescription('descripcion1', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion2', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion3', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion4', $lineaBalance, $balanceCalculado);
            }

            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion1');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion2');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion3');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion4');
        }

        $balanceFinal = [];
        foreach ($balanceCalculado as $lineaBalance) {
            $balanceFinal[] = $this->processLine($lineaBalance);
        }

        return $balanceFinal;
    }

    /**
     * Obtains the balances for each one of the sections of the balance sheet according to their assigned accounts.
     *
     * @return array
     */
    protected function getData()
    {

        $dateFrom = $this->dataBase->var2str($this->dateFrom);
        $dateTo = $this->dataBase->var2str($this->dateTo);
        $dateFromPrev = $this->dataBase->var2str($this->dateFromPrev);
        $dateToPrev = $this->dataBase->var2str($this->dateToPrev);

        $sql = 'SELECT cb.codbalance,cb.naturaleza,cb.descripcion1,cb.descripcion2,cb.descripcion3,cb.descripcion4,ccb.codcuenta,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFrom . ' AND ' . $dateTo . ' THEN pa.debe - pa.haber ELSE 0 END) saldo,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFromPrev . ' AND ' . $dateToPrev . ' THEN pa.debe - pa.haber ELSE 0 END) saldoPrev'
            . ' FROM co_cuentascbba ccb '
            . ' INNER JOIN co_codbalances08 cb ON ccb.codbalance = cb.codbalance '
            . ' INNER JOIN co_partidas pa ON SUBSTR(pa.codsubcuenta, 1, 1) BETWEEN "1" AND "5" AND pa.codsubcuenta LIKE CONCAT(ccb.codcuenta,"%")'
            . ' INNER JOIN co_asientos asto ON asto.idasiento = pa.idasiento AND asto.fecha BETWEEN ' . $dateFromPrev . ' AND ' . $dateTo
            . ' WHERER cb.naturaleza IN ("A", "P")'
            . ' GROUP BY 1, 2, 3, 4, 5, 6, 7 '
            . ' ORDER BY cb.naturaleza, cb.nivel1, cb.nivel2, cb.orden3, cb.nivel4';

        return $this->dataBase->select($sql);
    }

    /**
     * Process a balance values.
     *
     * @param string $description
     * @param array $linea
     * @param array $balance
     */
    private function processDescription($description, &$linea, &$balance)
    {
        $index = $linea[$description];
        if (empty($index)) {
            return;
        }

        if (!array_key_exists($index, $balance)) {
            $balance[$index] = [
                'descripcion' => $index,
                'saldo' => $linea['saldo'],
                'saldoprev' => $linea['saldoprev'], ];
        } else {
            $balance[$index]['saldo'] += $linea['saldo'];
            $balance[$index]['saldoprev'] += $linea['saldoprev'];
        }
    }

    /**
     * Process a line entry with correct format.
     *
     * @param array $line
     *
     * @return array
     */
    protected function processLine($line)
    {
        $line['saldo'] = $this->divisaTools->format($line['saldo'], FS_NF0, false);
        $line['saldoPrev'] = $this->divisaTools->format($line['saldoPrev'], FS_NF0, false);
        $line['descripcion'] = $this::fixHtml($line['descripcion']);
        return $line;
    }
}
