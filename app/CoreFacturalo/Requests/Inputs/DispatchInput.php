<?php

namespace App\CoreFacturalo\Requests\Inputs;

use App\CoreFacturalo\Requests\Inputs\Common\ActionInput;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use App\CoreFacturalo\Requests\Inputs\Common\LegendInput;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\Models\Tenant\Company;
use App\Models\Tenant\Dispatch;
use App\Models\Tenant\Item;
use Illuminate\Support\Str;
use Modules\Dispatch\Models\Dispatcher;
use Modules\Dispatch\Models\Driver;
use Modules\Dispatch\Models\Transport;

class DispatchInput
{
    public static function set($inputs)
    {
        $document_type_id = $inputs['document_type_id'];
        $series = $inputs['series'];
        $number = $inputs['number'];

        $company = Company::active();
        $soap_type_id = $company->soap_type_id;
        $number = Functions::newNumber($soap_type_id, $document_type_id, $series, $number, Dispatch::class);

        if (is_null($inputs['id'])) {
            Functions::validateUniqueDocument($soap_type_id, $document_type_id, $series, $number, Dispatch::class);
        }

        $filename = Functions::filename($company, $document_type_id, $series, $number);
        $establishment = EstablishmentInput::set($inputs['establishment_id']);
        $customer = PersonInput::set($inputs['customer_id']);
        $inputs['type'] = 'dispatch';
        $data = [
            'id' => Functions::valueKeyInArray($inputs, 'id'),
            'type' => $inputs['type'],
            'user_id' => auth()->id(),
            'external_id' => Str::uuid()->toString(),
            'establishment_id' => $inputs['establishment_id'],
            'establishment' => $establishment,
            'soap_type_id' => $soap_type_id,
            'state_type_id' => '01',
            'ubl_version' => '2.0',
            'filename' => $filename,
            'document_type_id' => $document_type_id,
            'series' => $series,
            'number' => $number,
            'date_of_issue' => $inputs['date_of_issue'],
            'time_of_issue' => $inputs['time_of_issue'],
            'customer_id' => $inputs['customer_id'],
            'customer' => $customer,
            'observations' => $inputs['observations'],
            'transport_mode_type_id' => $inputs['transport_mode_type_id'],
            'transfer_reason_type_id' => $inputs['transfer_reason_type_id'],
            'transfer_reason_description' => $inputs['transfer_reason_description'],
            'date_of_shipping' => $inputs['date_of_shipping'],
            'transshipment_indicator' => $inputs['transshipment_indicator'],
            'port_code' => $inputs['port_code'],
            'unit_type_id' => $inputs['unit_type_id'],
            'total_weight' => $inputs['total_weight'],
            'packages_number' => $inputs['packages_number'],
            'container_number' => $inputs['container_number'],
//            'license_plate' => (isset($inputs['license_plate'])) ? func_str_to_upper_utf8($inputs['license_plate']) : null,
            'origin' => self::origin($inputs),
            'delivery' => self::delivery($inputs),
            'dispatcher' => self::dispatcher($inputs),
            'driver' => self::driver($inputs),
            'transport_data' => self::transport($inputs),
            'items' => self::items($inputs),
            'legends' => LegendInput::set($inputs),
            'optional' => Functions::valueKeyInArray($inputs, 'optional'),
            'actions' => ActionInput::set($inputs),
            'reference_document_id' => Functions::valueKeyInArray($inputs, 'reference_document_id'),
            'reference_quotation_id' => Functions::valueKeyInArray($inputs, 'reference_quotation_id'),
            'reference_order_note_id' => Functions::valueKeyInArray($inputs, 'reference_order_note_id'),
            'reference_order_form_id' => Functions::valueKeyInArray($inputs, 'reference_order_form_id'),
            'reference_sale_note_id' => Functions::valueKeyInArray($inputs, 'reference_sale_note_id'),
            'secondary_license_plates' => self::secondary_license_plates($inputs),
            'related' => self::related($inputs),
            'order_form_external' => Functions::valueKeyInArray($inputs, 'order_form_external'),
            'additional_data' => Functions::valueKeyInArray($inputs, 'additional_data'),
            'origin_address_id' => Functions::valueKeyInArray($inputs, 'origin_address_id', 0),
            'delivery_address_id' => Functions::valueKeyInArray($inputs, 'delivery_address_id', 0),
            'driver_id' => self::getDriverId($inputs), //Functions::valueKeyInArray($inputs, 'driver_id'),
            'dispatcher_id' => self::getDispatcherId($inputs), //Functions::valueKeyInArray($inputs, 'dispatcher_id'),
            'transport_id' => self::getTransportId($inputs),// Functions::valueKeyInArray($inputs, 'transport_id'),
        ];

        if (isset($inputs['data_affected_document'])) {
            $data['data_affected_document'] = $inputs['data_affected_document'];
        }
        return $data;
    }


    /**
     *
     * Documento relacionado (DAM), usado para exportación
     *
     * @param  $inputs
     * @return array|null
     */
    private static function related($inputs)
    {
        if (array_key_exists('related', $inputs)) {
            $related = $inputs['related'];

            if (!empty($related)) return $related;
        }

        return null;
    }


    private static function origin($inputs)
    {
        if (array_key_exists('origin', $inputs)) {
            $origin = $inputs['origin'];
            $country_id = key_exists('country_id', $origin) ? $origin['country_id'] : 'PE';
            $address = $origin['address'];
            $location_id = $origin['location_id'][2] == '0' ? $origin['location_id'] : $origin['location_id'][2];
            $code = key_exists('code', $origin) ? $origin['code'] : '0000';

            return [
                'country_id' => $country_id,
                'location_id' => $location_id,
                'address' => $address,
                'code' => $code,
            ];
        }
        return null;
    }

    private static function delivery($inputs)
    {
        if (array_key_exists('delivery', $inputs)) {
            $delivery = $inputs['delivery'];
            $country_id = key_exists('country_id', $delivery) ? $delivery['country_id'] : 'PE';
            $address = $delivery['address'];
            $location_id = $delivery['location_id'][2] == '0' ? $delivery['location_id'] : $delivery['location_id'][2];
            $code = key_exists('code', $delivery) ? $delivery['code'] : '0000';

            return [
                'country_id' => $country_id,
                'location_id' => $location_id,
                'address' => $address,
                'code' => $code,
            ];
        }
        return null;
    }

    private static function dispatcher($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '01') {
            if (array_key_exists('dispatcher', $inputs)) {
                $dispatcher = $inputs['dispatcher'];
                $identity_document_type_id = $dispatcher['identity_document_type_id'];
                $number = $dispatcher['number'];
                $name = $dispatcher['name'];
                $number_mtc = (isset($dispatcher['number_mtc'])) ? $dispatcher['number_mtc'] : null;

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'number' => $number,
                    'name' => $name,
                    'number_mtc' => $number_mtc,
                ];
            }
        }
        return null;
    }

    private static function driver($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '02') {
            if (array_key_exists('driver', $inputs)) {
                $driver = $inputs['driver'];
                $identity_document_type_id = $driver['identity_document_type_id'];
                $number = $driver['number'];
                $name = $driver['name'];
                $license = $driver['license'];
                $telephone = $driver['telephone'];

                return [
                    'identity_document_type_id' => $identity_document_type_id,
                    'number' => $number,
                    'name' => $name,
                    'license' => $license,
                    'telephone' => $telephone,
                ];
            }
        }

        return null;
    }

    private static function transport($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '02') {
            if (array_key_exists('transport', $inputs)) {
                $transport = $inputs['transport'];
                $plate_number = $transport['plate_number'];
                $model = $transport['model'];
                $brand = $transport['brand'];

                return [
                    'plate_number' => $plate_number,
                    'model' => $model,
                    'brand' => $brand,
                ];
            }
        }

        return null;
    }

    private static function items($inputs)
    {
        if (array_key_exists('items', $inputs)) {
            $items = [];
            foreach ($inputs['items'] as $row) {
                $item = Item::find($row['item_id']);
                $itemDispatch = $row['item'] ?? [];
                $row['IdLoteSelected'] = $row['IdLoteSelected'] ?? $itemDispatch['IdLoteSelected'] ?? null;

                $temp = [
                    'item_id' => $item->id,
                    'item' => [
                        'description' => $item->description == 'REPLACE DESCRIPTION' ? $row['description'] : $item->description,
                        'model' => $item->model,
                        'item_type_id' => $item->item_type_id,
                        'internal_id' => $item->internal_id,
                        'item_code' => $item->item_code,
                        'item_code_gs1' => $item->item_code_gs1,
                        'unit_type_id' => $item->unit_type_id,
                        'IdLoteSelected' => $row['IdLoteSelected'] ?? null,
                        'lot_group' => $row['lot_group'] ?? null,
                    ],
                    'quantity' => $row['quantity'],
                    'name_product_pdf' => Functions::valueKeyInArray($row, 'name_product_pdf'),
                    'additional_data' => Functions::valueKeyInArray($row, 'additional_data'),
                ];

                if (isset($temp['item']['lot_group']['date_of_due'])) {
                    $temp['item']['date_of_due'] = $temp['item']['lot_group']['date_of_due'];
                } else {
                    $temp['item']['date_of_due'] = $itemDispatch['date_of_due'] ?? null;
                }
                $items[] = $temp;
            }
            return $items;
        }
        return null;
    }

    private static function secondary_license_plates($inputs)
    {
        if (array_key_exists('secondary_license_plates', $inputs)) {
            $secondary_license_plates = $inputs['secondary_license_plates'];
            $semitrailer = $secondary_license_plates['semitrailer'];
            return [
                'semitrailer' => $semitrailer,
            ];

        }
        return null;
    }

    private static function getDispatcherId($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '01') {
            if (key_exists('dispatcher_id', $inputs)) {
                return $inputs['dispatcher_id'];
            }
            $dispatcher = $inputs['dispatcher'];
            $record = Dispatcher::query()
                ->firstOrCreate([
                    'identity_document_type_id' => $dispatcher['identity_document_type_id'],
                    'number' => $dispatcher['number']
                ], [
                    'name' => $dispatcher['name'],
                    'number_mtc' => $dispatcher['number_mtc']
                ]);

            return $record->id;
        }
        return null;
    }

    private static function getDriverId($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '02') {
            if (key_exists('driver_id', $inputs)) {
                return $inputs['driver_id'];
            }
            $driver = $inputs['driver'];
            $record = Driver::query()
                ->firstOrCreate([
                    'identity_document_type_id' => $driver['identity_document_type_id'],
                    'number' => $driver['number']
                ], [
                    'name' => $driver['name'],
                    'license' => $driver['license'],
                    'telephone' => $driver['telephone']
                ]);

            return $record->id;
        }
        return null;
    }

    private static function getTransportId($inputs)
    {
        if ($inputs['transport_mode_type_id'] === '02') {
            if (key_exists('transport_id', $inputs)) {
                return $inputs['transport_id'];
            }
            $transport = $inputs['transport'];
            $record = Transport::query()
                ->firstOrCreate([
                    'plate_number' => $transport['plate_number']
                ], [
                    'model' => $transport['model'],
                    'brand' => $transport['brand']
                ]);

            return $record->id;
        }
        return null;
    }
}
