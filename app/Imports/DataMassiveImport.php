<?php

namespace App\Imports;

use App\Http\Controllers\Tenant\Api\ServiceController;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Series;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

class DataMassiveImport implements ToCollection
{
    use Importable;

    protected $data;

    public function collection(Collection $rows)
    {
        unset($rows[0]);
        if (empty($rows[1][0])) {
            $this->data = array();
            return;
        }

        $allData = array();

        $series = collect(Series::all())->transform(function ($row) {
            return [
                'id' => $row->id,
                'contingency' => (bool) $row->contingency,
                'document_type_id' => $row->document_type_id,
                'establishment_id' => $row->establishment_id,
                'number' => $row->number
            ];
        });

        $affectation_igv_types = array('10', '20', '30');

        foreach ($rows as $row) {
            if (empty($row[0])) {
                break;
            }

            $number = strval($row[0]);

            if (!array_key_exists($number, $allData)) {
                $customer = Person::firstOrNew(['number' => !empty($row[6]) ? $row[6] : $row[7]], ['type' => 'customers']);

                if(!$customer->id) {
                    $customer->fill(array(
                    'identity_document_type_id' => strlen($customer->number) == 11 ? 6 : 1, 
                    'name' => $row[8], 
                    'country_id' => 'PE',
                    'address' => $row[9] ));

                    $customer->save();
                }

                if ($customer) {
                    $customer_id = $customer->id;
                    $document_type_id = $customer->identity_document_type_id == '6' ? '01' : '03';
                    $series_id = $series->where('document_type_id', $document_type_id)->where('number', $row[1])->where('contingency', false)->first();
                    $customer_name = $customer->name;
                } else {
                    $customer_id = null;
                    $document_type_id = null;
                    $series_id = null;
                    $customer_name = 'CLIENTE NO REGISTRADO';
                }

                //Fecha del Documento
                $create_date = $row[3];
                $date_create = Carbon::createFromFormat('d/m/Y', $create_date);
                $date_document = $date_create->format('Y-m-d');
                $service = new ServiceController();
                $exchange_rate = $service->exchangeRateTest($date_document);

                //Documento
                $document = [
                    'item' => $number, //Added
                    'establishment_id' => 1,
                    'document_type_id' => $document_type_id,
                    'series_id' => $series_id == null ? null : $series_id['id'],
                    'number' => $series_id == null ? '-' : '#',
                    'date_of_issue' => $date_document,
                    'time_of_issue' => date("H:i:s"),
                    'customer_id' => $customer_id,
                    'currency_type_id' => $row[2] == 'PEN' ? 'PEN' : 'USD',
                    'purchase_order' => null,
                    'exchange_rate_sale' => floatval($exchange_rate['sale']),
                    'total_prepayment' => 0,
                    'total_charge' => 0,
                    'total_discount' => 0,
                    'total_exportation' => 0,
                    'total_free' => 0,
                    'total_taxed' => 0,
                    'total_unaffected' => 0,
                    'total_exonerated' => 0,
                    'total_igv' => 0,
                    'total_base_isc' => 0,
                    'total_base_isc' => 0,
                    'total_base_other_taxes' => 0,
                    'total_other_taxes' => 0,
                    'total_plastic_bag_taxes' => 0,
                    'total_taxes' => 0,
                    'total_value' => 0,
                    'total' => 0,
                    'operation_type_id' => '0101',
                    'date_of_due' => $date_document,
                    'items' => array(),
                    'charges' => array(),
                    'discounts' => array(),
                    'attributes' => array(),
                    'guides' => array(),
                    'payments' => array(),
                    'prepayments' => array(),
                    'additional_information' => null,
                    'has_prepayment' => false,
                    'actions' => [
                        'format_pdf' => 'a4'
                    ],
                    'hotel' => [],
                    'additional' => [
                        'series' => $series_id == null ? 'SERIE NO EXISTE' : $series_id['number'],
                        'customer_name' => $customer_name,
                        'has_null_items' => false,
                        'loading_button' => false
                    ]
                ];

                $allData[$number] = $document;
            }

            $item = Item::firstOrNew(['internal_id' => $row[10]], ['is_set' => false]);
            $affectation_igv_type = (string)'10'; //Por defecto todo es gravado

            if (!empty($row[17])) {
                $affectation_igv_type = (string)$row[17];
                
                if (!in_array($affectation_igv_type, $affectation_igv_types)) {
                    $document_item = [
                        'item_id' => null,
                        'item' => [
                            'id' => null,
                            'internal_id' =>  $row[10],
                            'description' => 'TIPO AFECTACION IGV INVALIDO'
                        ]
                    ];
                    array_push($allData[$number]['items'], $document_item);
                    continue;
                }
            }

            if(!$item->id) {
                $item->fill(array(
                    'description' => $row[11],
                    'item_type_id' => '01',
                    'unit_type_id' => 'NIU',
                    'currency_type_id' => 'PEN',
                    'sale_unit_price' => $row[14],
                    'has_igv' => true,
                    'purchase_unit_price' => 0,
                    'has_isc' => false,
                    'amount_plastic_bag_taxes' => 0.10,
                    'percentage_isc' => 0,
                    'suggested_price' => 0,
                    'sale_affectation_igv_type_id' => $affectation_igv_type,
                    'purchase_affectation_igv_type_id' => $affectation_igv_type,
                    'calculate_quantity' => false,
                    'image' => 'imagen-no-disponible.jpg',
                    'image_medium' => 'imagen-no-disponible.jpg',
                    'image_small' => 'imagen-no-disponible.jpg',
                    'stock' => 0,
                    'stock_min' => 0,
                    'percentage_of_profit' => 0,
                    'has_perception' => false,
                    'status' => true,
                    'apply_store' => false
                ));

                $item->save();
            }

            if ($item) {
                $document_item = [
                    'item_id' => $item->id,
                    'item' => [
                        'id' => $item->id,
                        'full_description' => $item->internal_id . ' - ' . $item->description,
                        'internal_id' =>  $item->internal_id,
                        'description' => $item->description == 'REPLACE DESCRIPTION' ? $row[11] : $item->description,
                        'currency_type_id' => $item->currency_type_id,
                        'currency_type_symbol' => $item->currency_type->symbol,
                        'sale_unit_price' => $item->sale_unit_price,
                        'purchase_unit_price' => $item->purchase_unit_price,
                        'unit_type_id' => $item->unit_type_id,
                        'sale_affectation_igv_type_id' => $item->sale_affectation_igv_type_id,
                        'purchase_affectation_igv_type_id' => $item->purchase_affectation_igv_type_id,
                        'calculate_quantity' => $item->calculate_quantity,
                        'has_igv' => $item->has_igv,
                        'amount_plastic_bag_taxes' => $item->amount_plastic_bag_taxes,
                        'item_unit_types' => array(),
                        // 'warehouses' => $item->warehouses,
                        'warehouses' => array(),
                        'unit_price' => $item->sale_unit_price,
                        'presentation' => [],
                    ],
                    'currency_type_id' => $item->currency_type_id,
                    'quantity' => $row[12],
                    'unit_value' => $affectation_igv_type == '10' ? floatval($row[14]) / 1.18 : $row[14],
                    'affectation_igv_type_id' => $affectation_igv_type,
                    'affectation_igv_type' => $item->sale_affectation_igv_type,
                    'total_base_igv' => $affectation_igv_type == '10' ? round(floatval($row[16]) / 1.18, 2) : $row[16],
                    'percentage_igv' => 18,
                    'total_igv' => $affectation_igv_type == '10' ? round(floatval($row[16]) - round(floatval($row[16]) / 1.18, 2), 2) : 0,
                    'system_isc_type_id' => null,
                    'total_base_isc' => 0,
                    'percentage_isc' => 0,
                    'total_isc' => 0,
                    'total_base_other_taxes' => 0,
                    'percentage_other_taxes' => 0,
                    'total_other_taxes' => 0,
                    'total_plastic_bag_taxes' => 0,
                    'total_taxes' => $affectation_igv_type == '10' ? round(floatval($row[16]) - round(floatval($row[16]) / 1.18, 2), 2) : 0,
                    'price_type_id' => '01',
                    'unit_price' => $row[14],
                    'total_value' => $affectation_igv_type == '10' ? round(floatval($row[16]) / 1.18, 2) : $row[16],
                    'total_discount' => 0,
                    'total_charge' => 0,
                    'total' => round(floatval($row[16]), 2),
                    'attributes' => array(),
                    'charges' => array(),
                    'discounts' => array()
                ];
            } else {
                $document_item = [
                    'item_id' => null,
                    'item' => [
                        'id' => null,
                        'internal_id' =>  $row[10],
                        'description' => 'PRODUCTO NO REGISTRADO'
                    ]
                ];
            }

            array_push($allData[$number]['items'], $document_item);
        }

        foreach ($allData as $data => $value) {
            $total_taxed = 0;
            $total_igv = 0;
            $total = 0;
            $has_null_items = 0;
            $total_unaffected = 0;
            $total_exonerated = 0;

            foreach ($value['items'] as $item_x) {

                if ($item_x['item_id'] == null) {
                    $has_null_items++;
                    continue;
                }

                if ($item_x['affectation_igv_type_id'] == '20') {
                    $total_exonerated += $item_x['total'];
                } else if ($item_x['affectation_igv_type_id'] == '30') {
                    $total_unaffected += $item_x['total'];
                }

                $total += $item_x['total'];
            }

            $total = round($total, 2); 
            $total_taxed = round(($total - $total_exonerated - $total_unaffected) / 1.18, 2);
            $total_igv = round(($total - $total_exonerated - $total_unaffected) - $total_taxed, 2);
            $total_value = $total_taxed + $total_exonerated + $total_unaffected;

            $allData[$data]["total_exonerated"] = $total_exonerated;
            $allData[$data]["total_unaffected"] = $total_unaffected;
            $allData[$data]["total_taxed"] = $total_taxed;
            $allData[$data]["total_igv"] = $total_igv;
            $allData[$data]["total_taxes"] = $total_igv;
            $allData[$data]["total_value"] = $total_value;
            $allData[$data]["total"] = $total;
            $allData[$data]["additional"]["has_null_items"] = $has_null_items > 0;
        }

        $this->data = array_values($allData);
    }

    public function getData()
    {
        return $this->data;
    }
}