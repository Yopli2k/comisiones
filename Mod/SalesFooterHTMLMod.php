<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

class SalesFooterHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['totalcomision'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'totalcomision') {
            return $this->totalcomision($i18n, $model);
        }
        return null;
    }

    private function totalcomision(Translator $i18n, SalesDocument $model): string
    {
        return empty($model->{'totalcomision'}) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('commission')
            . '<input type="number" name="totalcomision" value="' . $model->totalcomision . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}
