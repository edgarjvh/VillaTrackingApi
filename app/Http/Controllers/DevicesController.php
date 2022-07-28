<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Trace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    public function getDevices(): JsonResponse
    {
        $DEVICE = new Device();
        $devices = $DEVICE->with(['device_model', 'location'])->orderBy('license_plate')->get();
        return response()->json(['result' => 'OK', 'devices' => $devices]);
    }

    public function getDeviceById(Request $request): JsonResponse
    {
        $DEVICE = new Device();
        $id = $request->id ?? 0;

        $device = $DEVICE->where('id', $id)->first();
        return response()->json(['result' => 'OK', 'device' => $device]);
    }

    public function getDevicesByUser(Request $request): JsonResponse
    {
        $DEVICE = new Device();
        $user_id = $request->user_id ?? 0;

        $devices = $DEVICE->where('user_id', $user_id)->with(['last_traces'])->orderBy('license_plate')->get();
        return response()->json(['result' => 'OK', 'devices' => $devices]);
    }

    public function saveDevice(Request $request): JsonResponse
    {
        $DEVICE = new Device();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;
        $device_model_id = $request->device_model_id ?? 0;
        $imei = $request->imei ?? '';
        $expiration_date = $request->expiration_date ?? null;
        $speed_limit = $request->speed_limit ?? 0;
        $vehicle = $request->vehicle ?? '';
        $license_plate = $request->license_plate ?? '';
        $driver_name = $request->driver_name ?? '';
        $simcard_number = $request->simcard_number ?? '';
        $simcard_carrier = $request->simcard_carrier ?? '';
        $simcard_apn_name = $request->simcard_apn_name ?? '';
        $simcard_apn_user = $request->simcard_apn_user ?? '';
        $simcard_apn_pass = $request->simcard_apn_pass ?? '';
        $ip = $request->ip ?? '';
        $port = $request->port ?? 0;
        $additional_info = $request->additional_info ?? '';
        $status = $request->status ?? 1;
        $marker_icon_type = $request->marker_icon_type ?? '';
        $marker_icon_color = $request->marker_icon_color ?? '';
        $marker_icon_width = $request->marker_icon_width ?? null;
        $marker_icon_height = $request->marker_icon_height ?? null;
        $tail_color = $request->tail_color ?? '';
        $km_per_lt = $request->km_per_lt ?? 0;
        $cost_per_lt = $request->cost_per_lt ?? 0.00;

        $device = $DEVICE->updateOrCreate([
            'id' => $id
        ], [
            'user_id' => $user_id,
            'device_model_id' => $device_model_id,
            'imei' => $imei,
            'expiration_date' => $expiration_date,
            'speed_limit' => $speed_limit,
            'vehicle' => $vehicle,
            'license_plate' => $license_plate,
            'driver_name' => $driver_name,
            'simcard_number' => $simcard_number,
            'simcard_carrier' => $simcard_carrier,
            'simcard_apn_name' => $simcard_apn_name,
            'simcard_apn_user' => $simcard_apn_user,
            'simcard_apn_pass' => $simcard_apn_pass,
            'ip' => $ip,
            'port' => $port,
            'additional_info' => $additional_info,
            'status' => $status,
            'marker_icon_type' => $marker_icon_type,
            'marker_icon_color' => $marker_icon_color,
            'marker_icon_width' => $marker_icon_width,
            'marker_icon_height' => $marker_icon_height,
            'tail_color' => $tail_color,
            'km_per_lt' => $km_per_lt,
            'cost_per_lt' => $cost_per_lt
        ]);

        $device = $DEVICE->where('id', $device->id)->with(['last_traces'])->get();
        $devices = $DEVICE->where('user_id', $user_id)->with(['last_traces'])->orderBy('license_plate')->get();

        return response()->json(['result' => 'OK', 'device' => $device, 'devices' => $devices]);
    }

    public function deleteDevice(Request $request): JsonResponse
    {
        $DEVICE = new Device();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;

        $DEVICE->where('id', $id)->delete();
        $devices = $DEVICE->where('user_id', $user_id)->with(['last_traces'])->orderBy('license_plate')->get();

        return response()->json(['result' => 'OK', 'devices' => $devices]);
    }

    public function getDeviceHistory(Request $request): JsonResponse
    {
        $DEVICE = new Device();
        $TRACE = Trace::query();
        $imei = $request->imei ?? '';
        $date_from = $request->date_from ?? null;
        $date_to = $request->date_to ?? null;
        $history_type = $request->history_type ?? 'locations';

        $device = $DEVICE->where('imei', $imei)->first();

        $TRACE->where('imei', $imei);
        $TRACE->whereBetween('date_time', [$date_from, $date_to]);

        if ($history_type === 'alerts') {
            $TRACE->whereNotIn('alert', ['tracker']);
        }

        $rows = $TRACE->orderBy('date_time')->get();

        $traces = [];
        $last_trace = null;
        $coords = null;
        $count = count($rows);

        for ($i = 0; $i < $count; $i++) {
            $trace = new _Trace($rows[$i]);
            $trace->date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));
            $trace->last_date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));

            if ($history_type === 'alerts') {
                // si hay una ubicacion anterior
                if ($last_trace) {
                    // verificamos si la alerta corresponde a eventos no acumulables
                    if ($trace->alert === 'acc on' ||
                        $trace->alert === 'acc off' ||
                        $trace->alert === 'overspeed' ||
                        $trace->alert === 'geofence in' ||
                        $trace->alert === 'geofence out') {

                        //guardamos el ultimo punto
                        $coord = new Coord();
                        $coord->latitude = $last_trace->latitude;
                        $coord->longitude = $last_trace->longitude;

                        $coords[] = $coord;
                        $traces[] = $last_trace;

                        $last_trace = $trace;

                        //verificamos si el punto actual es el ultimo para guardarlo tambien
                        if (($i + 1) === $count) {
                            $coord = new Coord();
                            $coord->latitude = $trace->latitude;
                            $coord->longitude = $trace->longitude;

                            $coords[] = $coord;
                            $traces[] = $trace;
                        }
                    } else {
                        //verificamos que la alerta anterior sea distinta a la actual
                        if ($last_trace->alert !== $trace->alert) {
                            //guardamos el ultimo punto
                            $coord = new Coord();
                            $coord->latitude = $last_trace->latitude;
                            $coord->longitude = $last_trace->longitude;

                            $coords[] = $coord;
                            $traces[] = $last_trace;

                            $last_trace = $trace;

                            //verificamos si el punto actual es el ultimo para guardarlo tambien
                            if (($i + 1) === $count) {
                                $coord = new Coord();
                                $coord->latitude = $trace->latitude;
                                $coord->longitude = $trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $trace;
                            }
                        } else {
                            // verificamos si son el mismo punto
                            if ($last_trace->latitude === $trace->latitude && $last_trace->longitude === $trace->longitude) {
                                $last_trace->last_date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));

                                //verificamos si es el ultimo registro para guardar el punto anterior
                                if (($i + 1) === $count) {
                                    $coord = new Coord();
                                    $coord->latitude = $last_trace->latitude;
                                    $coord->longitude = $last_trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $last_trace;
                                }
                            } else {
                                // se verifica que la distancia sea mayor a 40 metros
                                if ($this->haversineGreatCircleDistance(
                                        $last_trace->latitude,
                                        $last_trace->longitude,
                                        $trace->latitude,
                                        $trace->longitude
                                    ) > 40) {
                                    //guardamos el ultimo punto
                                    $coord = new Coord();
                                    $coord->latitude = $last_trace->latitude;
                                    $coord->longitude = $last_trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $last_trace;

                                    $last_trace = $trace;

                                    //verificamos si el punto actual es el ultimo para guardarlo tambien
                                    if (($i + 1) === $count) {
                                        $coord = new Coord();
                                        $coord->latitude = $trace->latitude;
                                        $coord->longitude = $trace->longitude;

                                        $coords[] = $coord;
                                        $traces[] = $trace;
                                    }
                                } else {
                                    //se sobreescribe la ultima fecha del punto anterior
                                    $last_trace->last_date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));

                                    //verificamos si es el ultimo registro para guardar el ultimo punto
                                    if (($i + 1) === $count) {
                                        $coord = new Coord();
                                        $coord->latitude = $last_trace->latitude;
                                        $coord->longitude = $last_trace->longitude;

                                        $coords[] = $coord;
                                        $traces[] = $last_trace;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $last_trace = $trace;

                    //se verifica si es el ultimo punto
                    if (($i + 1) === $count) {
                        $coord = new Coord();
                        $coord->latitude = $trace->latitude;
                        $coord->longitude = $trace->longitude;

                        $coords[] = $coord;
                        $traces[] = $trace;
                    }
                }
            } else {
                //si hay una ubicacion anterior
                if ($last_trace) {
                    //comparamos primero si el punto anterior estaba en movimiento
                    if ($last_trace->speed > 0) {
                        //verificamos si en el nuevo punto sigue en movimiento a mas de 15 km/h
                        //para tratarlo como un nuevo punto
                        if ($trace->speed > 15) {
                            //guardamos el ultimo punto
                            $coord = new Coord();
                            $coord->latitude = $last_trace->latitude;
                            $coord->longitude = $last_trace->longitude;

                            $coords[] = $coord;
                            $traces[] = $last_trace;

                            $last_trace = $trace;

                            if (($i + 1) === $count) {
                                $coord = new Coord();
                                $coord->latitude = $trace->latitude;
                                $coord->longitude = $trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $trace;
                            }
                        } elseif ($trace->speed > 0) {
                            //si la velocidad actual es menor o igual a 15 km/h y mayor a 0
                            //se verifica la distancia entre el ultimo punto y el actual
                            //si es mayor a 40 metros se toma como un punto nuevo, de lo contrario se omite
                            if ($this->haversineGreatCircleDistance(
                                    $last_trace->latitude,
                                    $last_trace->longitude,
                                    $trace->latitude,
                                    $trace->longitude
                                ) > 40) {
                                //guardamos el ultimo punto
                                $coord = new Coord();
                                $coord->latitude = $last_trace->latitude;
                                $coord->longitude = $last_trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $last_trace;

                                $last_trace = $trace;

                                //verificamos si el punto actual es el ultimo para guardarlo tambien
                                if (($i + 1) === $count) {
                                    $coord = new Coord();
                                    $coord->latitude = $trace->latitude;
                                    $coord->longitude = $trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $trace;
                                }
                            } else {
                                //se omite el punto actual
                                //verificamos si es el ultimo registro para guardar el ultimo punto
                                if (($i + 1) === $count) {
                                    $coord = new Coord();
                                    $coord->latitude = $last_trace->latitude;
                                    $coord->longitude = $last_trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $last_trace;
                                }
                            }
                        } else {
                            // si la velocidad es 0 se toma como un punto nuevo
                            //guardamos el ultimo punto
                            $coord = new Coord();
                            $coord->latitude = $last_trace->latitude;
                            $coord->longitude = $last_trace->longitude;

                            $coords[] = $coord;
                            $traces[] = $last_trace;

                            $last_trace = $trace;

                            //verificamos si es el ultimo registro para guardar el punto actual
                            if (($i + 1) === $count) {
                                $coord = new Coord();
                                $coord->latitude = $trace->latitude;
                                $coord->longitude = $trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $trace;
                            }
                        }
                    } else {
                        // si la velocidad del ultimo punto es 0, comparamos primero si son el mismo punto
                        if ($last_trace->latitude === $trace->latitude && $last_trace->longitude === $trace->longitude) {
                            //se sobreescribe la ultima fecha del punto anterior
                            $last_trace->last_date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));

                            //verificamos si es el ultimo registro para guardar el ultimo punto
                            if (($i + 1) === $count) {
                                $coord = new Coord();
                                $coord->latitude = $last_trace->latitude;
                                $coord->longitude = $last_trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $last_trace;
                            }
                        } else {
                            //si no es el mismo punto se verifica primero que la velocidad sea mayor a 15 km/h
                            if ($trace->speed > 15) {
                                //guardamos el ultimo punto
                                $coord = new Coord();
                                $coord->latitude = $last_trace->latitude;
                                $coord->longitude = $last_trace->longitude;

                                $coords[] = $coord;
                                $traces[] = $last_trace;

                                $last_trace = $trace;

                                //verificamos si es el ultimo registro para guardar el punto actual
                                if (($i + 1) === $count) {
                                    $coord = new Coord();
                                    $coord->latitude = $trace->latitude;
                                    $coord->longitude = $trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $trace;
                                }
                            } else {
                                // si la velocidad es menor o igual a 15 se verifica que la distancia sea mayor a 40 metros
                                if ($this->haversineGreatCircleDistance(
                                        $last_trace->latitude,
                                        $last_trace->longitude,
                                        $trace->latitude,
                                        $trace->longitude
                                    ) > 40) {
                                    //guardamos el ultimo punto
                                    $coord = new Coord();
                                    $coord->latitude = $last_trace->latitude;
                                    $coord->longitude = $last_trace->longitude;

                                    $coords[] = $coord;
                                    $traces[] = $last_trace;

                                    $last_trace = $trace;

                                    //verificamos si el punto actual es el ultimo para guardarlo tambien
                                    if (($i + 1) === $count) {
                                        $coord = new Coord();
                                        $coord->latitude = $trace->latitude;
                                        $coord->longitude = $trace->longitude;

                                        $coords[] = $coord;
                                        $traces[] = $trace;
                                    }
                                } else {
                                    //se sobreescribe la ultima fecha del punto anterior
                                    $last_trace->last_date_time = date('Y-m-d H:i:s', strtotime($trace->date_time));

                                    //verificamos si es el ultimo registro para guardar el ultimo punto
                                    if (($i + 1) === $count) {
                                        $coord = new Coord();
                                        $coord->latitude = $last_trace->latitude;
                                        $coord->longitude = $last_trace->longitude;

                                        $coords[] = $coord;
                                        $traces[] = $last_trace;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $last_trace = $trace;

                    //se verifica si es el ultimo punto
                    if (($i + 1) === $count) {
                        $coord = new Coord();
                        $coord->latitude = $trace->latitude;
                        $coord->longitude = $trace->longitude;

                        $coords[] = $coord;
                        $traces[] = $trace;
                    }
                }
            }
        }

        $distance = 0;

        if (count($coords) > 1) {
            $last_coord = $coords[0];

            for ($i = 1; $i < count($coords); $i++) {
                $coord = $coords[$i];

                $distance = $distance + $this->haversineGreatCircleDistance(
                        $last_coord->latitude,
                        $last_coord->longitude,
                        $coord->latitude,
                        $coord->longitude
                    );

                $last_coord = $coord;
            }
        }
        $higher_speed = 0;
        $seconds_move = 0;
        $seconds_stop = 0;
        $last_status = null;
        $last_time = null;
        $seconds_ac_alarm = 0;
        $seconds_low_battery = 0;
        $seconds_no_gps = 0;
        $seconds_sensor_alarm = 0;

        if ($history_type === 'alerts') {
            if (count($traces) === 1) {
                $date1 = strtotime($traces[0]->date_time);
                $date2 = strtotime($traces[0]->last_date_time);
                $dif = $date2 - $date1;

                switch ($traces[0]->alert) {
                    case 'ac alarm' :
                        $seconds_ac_alarm = $dif;
                        break;
                    case 'low battery' :
                        $seconds_low_battery = $dif;
                        break;
                    case 'no gps' :
                        $seconds_no_gps = $dif;
                        break;
                    case 'sensor alarm' :
                        $seconds_sensor_alarm = $dif;
                        break;
                }
            } else {
                for ($i = 0; count($traces); $i++) {
                    $alert = $traces[$i]->alert;
                    $date_time = $traces[$i]->date_time;
                    $last_date_time = $traces[$i]->last_date_time;

                    $date1 = strtotime($date_time);
                    $date2 = strtotime($last_date_time);
                    $dif = $date2 - $date1;

                    switch ($alert) {
                        case 'ac alarm' :
                            if (!$last_status) {
                                $last_status = $alert;
                                $last_time = $last_date_time;
                                $seconds_ac_alarm = $dif;
                            } else {
                                if ($last_status === $alert) {
                                    $date0 = strtotime($last_time);
                                    $dif = $dif + ($date1 - $date0);
                                    $last_time = $last_date_time;
                                    $seconds_ac_alarm = $dif;
                                } else {
                                    $date0 = strtotime($last_time);
                                    $last_dif = $date1 - $date0;
                                    $seconds_low_battery += $last_status === 'low battery' ? $last_dif : 0;
                                    $seconds_no_gps += $last_status === 'no gps' ? $last_dif : 0;
                                    $seconds_sensor_alarm += $last_status === 'sensor alarm' ? $last_dif : 0;

                                    $last_status = $alert;
                                    $last_time = $last_date_time;
                                    $seconds_ac_alarm += $dif;
                                }
                            }
                            break;
                        case 'low battery' :
                            if (!$last_status) {
                                $last_status = $alert;
                                $last_time = $last_date_time;
                                $seconds_low_battery = $dif;
                            } else {
                                if ($last_status === $alert) {
                                    $date0 = strtotime($last_time);
                                    $dif = $dif + ($date1 - $date0);
                                    $last_time = $last_date_time;
                                    $seconds_low_battery = $dif;
                                } else {
                                    $date0 = strtotime($last_time);
                                    $last_dif = $date1 - $date0;
                                    $seconds_ac_alarm += $last_status === 'ac alarm' ? $last_dif : 0;
                                    $seconds_no_gps += $last_status === 'no gps' ? $last_dif : 0;
                                    $seconds_sensor_alarm += $last_status === 'sensor alarm' ? $last_dif : 0;

                                    $last_status = $alert;
                                    $last_time = $last_date_time;
                                    $seconds_low_battery += $dif;
                                }
                            }
                            break;
                        case 'no gps' :
                            if (!$last_status) {
                                $last_status = $alert;
                                $last_time = $last_date_time;
                                $seconds_no_gps = $dif;
                            } else {
                                if ($last_status === $alert) {
                                    $date0 = strtotime($last_time);
                                    $dif = $dif + ($date1 - $date0);
                                    $last_time = $last_date_time;
                                    $seconds_no_gps = $dif;
                                } else {
                                    $date0 = strtotime($last_time);
                                    $last_dif = $date1 - $date0;
                                    $seconds_ac_alarm += $last_status === 'ac alarm' ? $last_dif : 0;
                                    $seconds_low_battery += $last_status === 'low battery' ? $last_dif : 0;
                                    $seconds_sensor_alarm += $last_status === 'sensor alarm' ? $last_dif : 0;

                                    $last_status = $alert;
                                    $last_time = $last_date_time;
                                    $seconds_no_gps += $dif;
                                }
                            }
                            break;

                        case 'sensor alarm' :
                            if (!$last_status) {
                                $last_status = $alert;
                                $last_time = $last_date_time;
                                $seconds_sensor_alarm = $dif;
                            } else {
                                if ($last_status === $alert) {
                                    $date0 = strtotime($last_time);
                                    $dif = $dif + ($date1 - $date0);
                                    $last_time = $last_date_time;
                                    $seconds_sensor_alarm = $dif;
                                } else {
                                    $date0 = strtotime($last_time);
                                    $last_dif = $date1 - $date0;
                                    $seconds_ac_alarm += $last_status === 'ac alarm' ? $last_dif : 0;
                                    $seconds_low_battery += $last_status === 'low battery' ? $last_dif : 0;
                                    $seconds_no_gps += $last_status === 'no gps' ? $last_dif : 0;

                                    $last_status = $alert;
                                    $last_time = $last_date_time;
                                    $seconds_sensor_alarm += $dif;
                                }
                            }
                            break;
                    }
                }
            }
        } else {
            if (count($traces) === 1) {
                $date1 = strtotime($traces[0]->date_time);
                $date2 = strtotime($traces[0]->last_date_time);
                $dif = $date2 - $date1;

                $seconds_move = $traces[0]->speed > 0 ? $dif : 0;
                $seconds_stop = $traces[0]->speed === 0 ? $dif : 0;
            } else {
                for ($i = 0; $i < count($traces); $i++) {
                    $trace = $traces[$i];
                    $speed = $trace->speed;
                    $higher_speed = max($speed, $higher_speed);

                    if ($speed > 0) {
                        if (!$last_status) {
                            $last_status = 'move';
                            $last_time = $trace->date_time;
                        } else {
                            if ($last_status === 'stop') {
                                $date1 = strtotime($last_time);
                                $date2 = strtotime($trace->last_date_time);
                                $dif = $date2 - $date1;

                                $seconds_stop += $dif;
                                $last_status = 'move';
                                $last_time = $trace->last_date_time;
                            }

                            if (($i + 1) === count($traces)) {
                                $date1 = strtotime($trace->date_time);
                                $date2 = strtotime($trace->last_date_time);
                                $dif = $date2 - $date1;
                                $seconds_move += $dif;
                            }
                        }
                    } else {
                        if (!$last_status) {
                            $last_status = 'stop';
                            $last_time = $trace->date_time;
                        } else {
                            if ($last_status === 'move') {
                                $date1 = strtotime($last_time);
                                $date2 = strtotime($trace->last_date_time);
                                $dif = $date2 - $date1;

                                $seconds_move += $dif;
                                $last_status = 'stop';
                                $last_time = $trace->last_date_time;
                            }

                            if (($i + 1) === count($traces)) {
                                $date1 = strtotime($trace->date_time);
                                $date2 = strtotime($trace->last_date_time);
                                $dif = $date2 - $date1;
                                $seconds_stop += $dif;
                            }
                        }
                    }
                }
            }
        }

        $trace_history = new TraceHistory();
        $trace_history->result = 'OK';
        $trace_history->lastCount = count($rows);
        $trace_history->newCount = count($traces);
        $trace_history->distance = $distance;
        $trace_history->fuelConsumption = $device->km_per_lt === 0 ? 0 : ($distance / 1000) / $device->km_per_lt;
        $trace_history->timeMove = $seconds_move;
        $trace_history->timeStop = $seconds_stop;
        $trace_history->alertsTime = [
            new AlertTime('ac alarm', $seconds_ac_alarm),
            new AlertTime('low battery', $seconds_low_battery),
            new AlertTime('no gps', $seconds_no_gps),
            new AlertTime('sensor alarm', $seconds_sensor_alarm),
        ];
        $trace_history->traces = $traces;
        $trace_history->coords = $coords;
        $trace_history->higherSpeed = $higher_speed;

        return response()->json($trace_history);
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}

class _Trace
{
    var $id;
    var $imei;
    var $alert;
    var $date_time;
    var $fix;
    var $latitude;
    var $longitude;
    var $speed;
    var $heading;
    var $satellites;
    var $ignition;
    var $fuel;
    var $doors;
    var $battery;
    var $ip;
    var $port;

    public function __construct($obj)
    {
        $this->id = $obj->id ?? '';
        $this->imei = $obj->imei ?? '';
        $this->alert = $obj->alert ?? '';
        $this->date_time = $obj->date_time ?? null;
        $this->fix = $obj->fix ?? '';
        $this->latitude = $obj->latitude ?? 0.00;
        $this->longitude = $obj->longitude ?? 0.00;
        $this->speed = $obj->speed ?? 0;
        $this->heading = $obj->heading ?? 0;
        $this->satellites = $obj->satellites ?? 0;
        $this->ignition = $obj->ignition ?? 0;
        $this->fuel = $obj->fuel ?? 0;
        $this->doors = $obj->doors ?? 0;
        $this->battery = $obj->battery ?? 0;
        $this->ip = $obj->ip ?? '';
        $this->port = $obj->port ?? 0;
    }
}

class Coord
{

}

class AlertTime
{
    var $name;
    var $seconds;

    public function __construct($name, $seconds)
    {
        $this->name = $name;
        $this->seconds = $seconds;
    }
}

class TraceHistory
{

}
