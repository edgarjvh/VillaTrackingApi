<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeofencesController extends Controller
{
    public function getGeofences(): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $geofences = $GEOFENCE->orderBy('name')->get();
        return response()->json(['result' => 'OK', 'geofences' => $geofences]);
    }

    public function getGeofenceById(Request $request): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $id = $request->id ?? 0;

        $geofence = $GEOFENCE->where('id', $id)->first();
        return response()->json(['result' => 'OK', 'geofence' => $geofence]);
    }

    public function getGeofencesByUser(Request $request): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $user_id = $request->user_id ?? 0;

        $geofences = $GEOFENCE->where('user_id', $user_id)->withCount('devices')->orderBy('name')->get();
        return response()->json(['result' => 'OK', 'geofences' => $geofences]);
    }

    public function saveGeofence(Request $request): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;
        $name = $request->name ?? '';
        $description = $request->description ?? '';
        $type = $request->type ?? '';
        $points = $request->points ?? '';
        $center = $request->center ?? '';
        $radius = $request->radius ?? 0;
        $status = $request->status ?? 1;

        $geofence = $GEOFENCE->updateOrCreate([
            'id' => $id
        ], [
            'user_id' => $user_id,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'points' => $points,
            'center' => $center,
            'radius' => $radius,
            'status' => $status
        ]);

        $geofence = $GEOFENCE->where('id', $geofence->id)->withCount(['devices'])->first();
        $geofences = $GEOFENCE->where('user_id', $user_id)->withCount(['devices'])->orderBy('name')->get();

        return response()->json(['result' => 'OK', 'geofence' => $geofence, 'geofences' => $geofences]);
    }

    public function deleteGeofence(Request $request):JsonResponse{
        $GEOFENCE = new Geofence();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;

        $GEOFENCE->where('id', $id)->delete();

        $geofences = $GEOFENCE->where('user_id', $user_id)->withCount(['devices'])->orderBy('name')->get();
        return response()->json(['result' => 'OK', 'geofences' => $geofences]);
    }

    public function getGeofenceDevices(Request $request): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $geofences = $GEOFENCE->with(['devices'])->withCount(['devices'])->get();
        return response()->json(['result' => 'OK', 'geofences' => $geofences]);
    }

    public function saveGeofenceDevices(Request $request): JsonResponse
    {
        $GEOFENCE = new Geofence();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;
        $devices_id = $request->devices_id;

        $geofence = $GEOFENCE->where('id', $id)->first();

        $geofence->devices()->sync($devices_id);

        $geofences = $GEOFENCE->where('user_id', $user_id)->with(['devices'])->withCount(['devices'])->get();

        return response()->json(['result' => 'OK', 'geofences' => $geofences]);
    }
}
