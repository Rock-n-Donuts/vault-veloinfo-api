<?php
declare(strict_types=1);

namespace Rockndonuts\Hackqc;

class PolygonHelper
{
    private function pointOnVertex($point, $vertices)
    {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
    }

    private function coordsToPoint($coords)
    {
        if (!is_array($coords)) {
            return ['x' => 0, 'y' => 0];
        }
        return array("x" => $coords[0], "y" => $coords[1]);
    }

    private function pointInPolygon($point, $polygon, $pointOnVertex = true)
    {
        // Transform string coordinates into arrays with x and y values
        $point = $this->coordsToPoint($point);
        $vertices = array();
        foreach ($polygon as $vertex) {
            $vertices[] = $this->coordsToPoint($vertex);
        }

        // Check if the point sits exactly on a vertex
        if ($pointOnVertex == true && $this->pointOnVertex($point, $vertices) == true) {
            return "vertex";
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i = 1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                return "boundary";
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return "boundary";
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return "inside";
        } else {
            return "outside";
        }
    }

    public function getBoroughNameFromLocation($point): ?array
    {
        if (is_string($point)) {
            $point = explode(",", $point);
        }

        $sectorData = $this->getSectorFromLocation($point);

        if (!is_null($sectorData)) {
            $bName = $this->getSerializedBoroughName($sectorData['borough_code'], $sectorData['borough_name']);
            return ['sector' => $sectorData['sector_name'], 'borough' => $bName];
        }

        $data = file_get_contents(__DIR__ . '/boroughs.json');
        $json = json_decode($data, true);
        $boroughs = [];

        foreach ($json['features'] as $borough) {
            $props = $borough['properties'];
            $boroughs[$props['NOM']] = ['polygons' => $borough['geometry']['coordinates']];
        }

        $boroughName = null;
        foreach ($boroughs as $name => $borough) {
            foreach ($borough['polygons'] as $polygon) {
                if ($this->pointInPolygon($point, $polygon) != 'outside') {
                    $boroughName = $name;
                    break;
                }
            }
        }

        return ['sector' => null, 'borough' => $boroughName];
    }

    public function getSectorFromLocation(mixed $point): ?array
    {
        $data = file_get_contents(__DIR__ . '/plowing_sectors.geojson');
        $json = json_decode($data, true);
        $boroughs = [];

        foreach ($json['features'] as $borough) {
            $props = $borough['properties'];
            $coordsData = $borough['geometry']['coordinates'];
            $polys = array_shift($coordsData);
            $holes = $coordsData;

            if (empty($holes)) {
                $holes = [];
            }
            if (array_key_exists($props['NomSecteur'], $boroughs)) {
                $boroughs[$props['NomSecteur']]['polys'][] = [...$polys];

                $boroughs[$props['NomSecteur']]['holes'][] = [...$holes];
            } else {
                $boroughs[$props['NomSecteur']] = [
                    'polys' => [$polys],
                    'holes' => [$holes],
                    'borough_code' => $props['ArrondissementCode'],
                    'borough_name' => $props['Arrondissement'],
                    'sector_name' => $props['NomSecteur'],
                ];
            }
        }

        $sector = null;

        foreach ($boroughs as $sectorData) {
            $skip = false;

            if (!empty(array_filter($sectorData['holes']))) {
                foreach ($sectorData['holes'] as $holes) {
                    if (empty(array_filter($holes))) {
                        continue;
                    }
                    foreach ($holes as $hole) {
                        if ($this->pointInPolygon($point, $hole) != 'outside') {
                            $skip = true;
                        }
                    }
                }
            }
            if ($skip) {
                break;
            }
            foreach ($sectorData['polys'] as $polygon) {
                if ($this->pointInPolygon($point, $polygon) != 'outside') {
                    $sector = ['borough_code' => $sectorData['borough_code'], 'borough_name' => $sectorData['borough_name'], 'sector_name' => $sectorData['sector_name'], 'data' => $sectorData];
                }
            }
        }

        return $sector;
    }

    private function getSerializedBoroughName(mixed $bName, $sName)
    {
        return match ($bName) {
            "RDP" => 'Rivière-des-Prairies-Pointe-aux-Trembles',
            "VMA" => 'Ville-Marie',
            "S-O" => 'Le Sud-Ouest',
            "PMR" => 'Le Plateau-Mont-Royal',
            "AHU" => 'Ahuntsic-Cartierville',
            "RPP" => 'Rosemont-La Petite-Patrie',
            "VSP" => 'Villeray-Saint-Michel-Parc-Extension',
            "CDN" => 'Côte-des-Neiges-Notre-Dame-de-Grâce',
            "MHM" => 'Mercier-Hochelaga-Maisonneuve',
            "ANJ" => 'Anjou',
            "LAC" => 'Lachine',
            "LAS" => 'LaSalle',
            "MTN" => 'Montréal-Nord',
            "OUT" => 'Outremont',
            "PRF" => 'Pierrefonds-Roxboro',
            "SLA" => 'Saint-Laurent',
            "SLE" => 'Saint-Laurent',
            "VER" => 'Verdun',
            "IBI" => 'L\'Île-Bizard-Sainte-Geneviève',
            "WES" => 'Westmount',
            default => $sName

        };
    }
}
