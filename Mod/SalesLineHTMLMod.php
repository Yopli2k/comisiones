<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Contract\SalesLineModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;

class SalesLineHTMLMod implements SalesLineModInterface
{
    public function apply(SalesDocument &$model, array &$lines, array $formData): void
    {
    }

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id): void
    {
    }

    public function assets(): void
    {
    }

    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        return null;
    }

    public function map(array $lines, SalesDocument $model): array
    {
        $map = [];
        $num = 0;
        foreach ($lines as $line) {
            $num++;
            $idlinea = $line->idlinea ?? 'n' . $num;
            $map['porcomision_' . $idlinea] = $line->porcomision;
        }
        return $map;
    }

    public function newModalFields(): array
    {
        return ['porcomision'];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newTitles(): array
    {
        return [];
    }

    public function renderField(string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        if ($field === 'porcomision') {
            return $this->porcomision($idlinea, $line, $model);
        }
        return null;
    }

    public function renderTitle(SalesDocument $model, string $field): ?string
    {
        return null;
    }

    private function porcomision($idlinea, $line, $model): string
    {
        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::lang()->trans('percentage-commission')
            . '<input type="number" name="porcomision_' . $idlinea . '" value="' . $line->porcomision . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}