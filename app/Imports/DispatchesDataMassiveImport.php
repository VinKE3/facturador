<?php

namespace App\Imports;

use App\Models\Tenant\Catalogs\District;
use App\Models\Tenant\Catalogs\TransferReasonType;
use App\Models\Tenant\Catalogs\TransportModeType;
use App\Models\Tenant\Catalogs\UnitType;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Series;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Modules\Dispatch\Models\Dispatcher;
use Modules\Dispatch\Models\Driver;
use Modules\Dispatch\Models\Transport;
use Illuminate\Support\Facades\Log;

class DispatchesDataMassiveImport implements ToCollection
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

        $series = collect(Series::where('document_type_id', '09')->get())->transform(function ($row) {
            return [
                'id' => $row->id,
                'contingency' => (bool) $row->contingency,
                'document_type_id' => $row->document_type_id,
                'establishment_id' => $row->establishment_id,
                'number' => $row->number
            ];
        });

        foreach ($rows as $row) {
            if (empty($row[0])) {
                break;
            }

            $number = strval($row[0]);

            if (!array_key_exists($number, $allData)) {
                $customer = Person::firstOrNew(['number' => $row[4]], ['type' => 'customers']);

                if (!$customer->id) {
                    $customer->fill(array(
                        'identity_document_type_id' => strlen($customer->number) == 11 ? 6 : 1,
                        'name' => $row[5],
                        'country_id' => 'PE',
                        'address' => $row[6]
                    ));

                    $customer->save();
                }

                if ($customer) {
                    $customer_id = $customer->id;
                    $document_type_id = $customer->identity_document_type_id == '6' ? '01' : '03';
                    $series_id = $series->where('number', $row[1])->where('contingency', false)->first();
                    $customer_name = $customer->name;
                } else {
                    $customer_id = null;
                    $document_type_id = null;
                    $series_id = null;
                    $customer_name = 'CLIENTE NO REGISTRADO';
                }

                //Fecha del Documento
                $create_date = $row[2];
                $date_create = Carbon::createFromFormat('d/m/Y', $create_date);
                $date_of_issue = $date_create->format('Y-m-d');

                //Fecha de Traslado
                $create_date = $row[3];
                $date_create = Carbon::createFromFormat('d/m/Y', $create_date);
                $date_of_shipping = $date_create->format('Y-m-d');

                $transport_mode_type = TransportModeType::where('id', $row[7])->first();
                $transfer_reason_type = TransferReasonType::where('id', $row[8])->first();
                $unit_type = UnitType::where('id', $row[10])->first();
                $origin_location = District::where('id', $row[13])->first();
                $delivery_location = District::where('id', $row[15])->first();
                $dispatcher = Dispatcher::where('number', $row[17])->first();
                $driver = Driver::where('number', $row[18])->first();
                $transport = Transport::where('plate_number', $row[19])->first();

                $has_errors = 0;
                $has_errors = $customer_id == null ? $has_errors + 1 : $has_errors;
                $has_errors = $series_id == null ? $has_errors + 1 : $has_errors;
                $has_errors = !$transport_mode_type ? $has_errors + 1 : $has_errors;
                $has_errors = !$transfer_reason_type ? $has_errors + 1 : $has_errors;
                $has_errors = !$unit_type || !in_array($row[10], ['KGM', 'TNE']) ? $has_errors + 1 : $has_errors;
                $has_errors = !$origin_location ? $has_errors + 1 : $has_errors;
                $has_errors = !$delivery_location ? $has_errors + 1 : $has_errors;
                $has_errors = $row[07] == '01' && !$dispatcher && $driver ? $has_errors + 1 : $has_errors;
                $has_errors = $row[07] == '02' && !$driver && $dispatcher ? $has_errors + 1 : $has_errors;
                $has_errors = $row[07] == '02' && !$transport ? $has_errors + 1 : $has_errors;

                $dispatch = [
                    'id' => null,
                    'item' => $number, //Added
                    'establishment_id' => 1,
                    'document_type_id' => '09',
                    'series' => $series_id == null ? null : $series_id['number'],
                    'number' => '#',
                    'date_of_issue' => $date_of_issue,
                    'time_of_issue' => date("H:i:s"),
                    'customer_id' => $customer_id,
                    'observations' => $row[20],
                    'order_form_external' => null,
                    'transport_mode_type_id' => $transport_mode_type ? $transport_mode_type->id : null,
                    'transfer_reason_description' => $row[9],
                    'transfer_reason_type_id' => $transfer_reason_type ? $transfer_reason_type->id : null,
                    'date_of_shipping' => $date_of_shipping,
                    'transshipment_indicator' => false,
                    'port_code' => null,
                    'unit_type_id' => $unit_type ? $unit_type->id : null,
                    'total_weight' => $row[11],
                    'packages_number' => $row[12],
                    'container_number' => null,
                    'reference_order_form_id' => null,
                    'related' => [],
                    'origin' => [
                        'address' => $row[14],
                        'location_id' => $origin_location ? [
                            substr($origin_location->id, 0, 2),
                            substr($origin_location->id, 0, 4),
                            $origin_location->id
                        ] : null,
                    ],
                    'delivery' => [
                        'address' => $row[16],
                        'address_id' => $row[16],
                        'country_id' => 'PE',
                        'location_id' => $delivery_location ? [
                            substr($delivery_location->id, 0, 2),
                            substr($delivery_location->id, 0, 4),
                            $delivery_location->id
                        ] : null,
                    ],
                    'dispatcher_id' => $dispatcher ? $dispatcher->id : null,
                    'dispatcher' => $dispatcher ? [
                        'identity_document_type_id' => $dispatcher->identity_document_type_id,
                        'name' => $dispatcher->name,
                        'number' => $dispatcher->number,
                        'number_mtc' => $dispatcher->number_mtc
                    ] : null,
                    'driver_id' => $driver ? $driver->id : null,
                    'driver' => $driver ? [
                        'identity_document_type_id' => $driver->identity_document_type_id,
                        'lastnames' => $driver->name,
                        'license' => $driver->license,
                        'name' => $driver->name,
                        'number' => $driver->number,
                        'telephone' => $driver->telephone
                    ] : null,
                    'secondary_license_plates' => [
                        'semitrailer' => null
                    ],
                    'transport_id' => $transport ? $transport->id : null,
                    'transport' => $transport ? [
                        'brand' => $transport->brand,
                        'id' => $transport->id,
                        'model' => $transport->model,
                        'plate_number' => $transport->plate_number,
                    ] : null,
                    'items' => array(),
                    'legends' => array(),
                    'additional' => [
                        'series' => $series_id == null ? 'SERIE NO EXISTE' : $series_id['number'],
                        'customer_name' => $customer_name,
                        'transport_mode_type_description' => $transport_mode_type ? $transport_mode_type->description : '',
                        'transfer_reason_type_description' => $transfer_reason_type ? $transfer_reason_type->description : '',
                        'unit_type_description' => $unit_type ? $unit_type->description : '',
                        'origin_location_description' => $origin_location ? $origin_location->province->department->description . ' / ' . $origin_location->province->description . ' / ' . $origin_location->description : '',
                        'delivery_location_description' => $delivery_location ? $delivery_location->province->department->description . ' / ' . $delivery_location->province->description . ' / ' . $delivery_location->description : '',
                        'dispatcher_identity_document_type_description' => $dispatcher ? $dispatcher->identity_document_type->description : '',
                        'driver_identity_document_type_description' => $driver ? $driver->identity_document_type->description : '',
                        // 'has_null_items' => false,
                        'has_errors' => $has_errors,
                        'loading_button' => false
                    ]
                ];

                $allData[$number] = $dispatch;
            }

            if (!empty($row[21])) {
                $item = Item::firstOrNew(['internal_id' => $row[21]], ['is_set' => false]);

                if (!$item->id) {
                    $item->fill(array(
                        'description' => $row[22],
                        'item_type_id' => '01',
                        'unit_type_id' => 'NIU',
                        'currency_type_id' => 'PEN',
                        'sale_unit_price' => 0.01,
                        'has_igv' => true,
                        'purchase_unit_price' => 0,
                        'has_isc' => false,
                        'amount_plastic_bag_taxes' => 0.10,
                        'percentage_isc' => 0,
                        'suggested_price' => 0,
                        'sale_affectation_igv_type_id' => '10',
                        'purchase_affectation_igv_type_id' => '10',
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
            } else {
                $item = Item::where('description', 'REPLACE DESCRIPTION')->first();
            }

            if ($item) {
                $dispatch_item = [
                    'IdLoteSelected' => '',
                    'attributes' => null,
                    'description' => $item->description == 'REPLACE DESCRIPTION' ? $row[22] : $item->description,
                    'id' => $item->id,
                    'internal_id' => $item->internal_id,
                    'item_id' => $item->id,
                    'lot_group' => null,
                    'quantity' => $row[23],
                    'unit_type_id' => $item->unit_type_id,
                ];
            } else {
                $dispatch_item = [
                    'item_id' => null,
                    'internal_id' =>  $row[21],
                    'description' => 'PRODUCTO NO REGISTRADO',
                    'quantity' => 0
                ];
            }

            array_push($allData[$number]['items'], $dispatch_item);
        }

        $this->data = array_values($allData);
    }

    public function getData()
    {
        return $this->data;
    }
}
