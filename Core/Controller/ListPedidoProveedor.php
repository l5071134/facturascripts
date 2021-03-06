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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the PedidoProveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPedidoProveedor extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('PedidoProveedor', 'ListPedidoProveedor');
        $this->addSearchFields('ListPedidoProveedor', ['codigo', 'numproveedor', 'observaciones']);

        $this->addFilterDatePicker('ListPedidoProveedor', 'date', 'date', 'fecha');
        $this->addFilterNumber('ListPedidoProveedor', 'total', 'total');

        $where = [new DataBaseWhere('tipodoc', 'PedidoProveedor')];
        $this->addFilterSelect('ListPedidoProveedor', 'idestado', 'estados_documentos', 'idestado', 'nombre', $where);

        $this->addFilterSelect('ListPedidoProveedor', 'codalmacen', 'almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPedidoProveedor', 'codserie', 'series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPedidoProveedor', 'codpago', 'formaspago', 'codpago', 'descripcion');
        $this->addFilterAutocomplete('ListPedidoProveedor', 'codproveedor', 'proveedores', 'codproveedor', 'nombre');

        $this->addOrderBy('ListPedidoProveedor', 'codigo', 'code');
        $this->addOrderBy('ListPedidoProveedor', 'fecha', 'date', 2);
        $this->addOrderBy('ListPedidoProveedor', 'total', 'amount');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'orders';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }
}
