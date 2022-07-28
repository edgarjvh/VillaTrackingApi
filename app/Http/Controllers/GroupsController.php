<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupsController extends Controller
{
    public function getGroups(): JsonResponse
    {
        $GROUP = new Group();
        $groups = $GROUP->withCount(['devices'])->get();


        return response()->json(['result' => 'OK', 'groups' => $groups]);
    }

    public function getGroupsById(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $id = $request->id ?? 0;

        $group = $GROUP->where('id', $id)->withCount(['devices'])->first();
        return response()->json(['result' => 'OK', 'group' => $group]);
    }

    public function getGroupsByUser(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $user_id = $request->user_id ?? 0;

        $groups = $GROUP->where('user_id', $user_id)->withCount(['devices'])->orderBy('name')->get();
        return response()->json(['result' => 'OK', 'groups' => $groups]);
    }

    public function saveGroup(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;
        $name = $request->name ?? '';
        $status = $request->status ?? 1;

        $group = $GROUP->updateOrCreate([
            'id' => $id
        ], [
            'user_id' => $user_id,
            'name' => $name,
            'status' => $status
        ]);

        $group = $GROUP->where('id', $group->id)->withCount(['devices'])->first();
        $groups = $GROUP->where('user_id', $user_id)->withCount(['devices'])->get();

        return response()->json(['result' => 'OK', 'group' => $group, 'groups' => $groups]);
    }

    public function deleteGroup(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;

        $GROUP->where('id', $id)->delete();

        $groups = $GROUP->where('user_id', $user_id)->withCount(['devices'])->get();

        return response()->json(['result' => 'OK', 'groups' => $groups]);
    }

    public function getGroupDevices(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $groups = $GROUP->with(['devices'])->withCount(['devices'])->get();
        return response()->json(['result' => 'OK', 'groups' => $groups]);
    }

    public function saveGroupDevices(Request $request): JsonResponse
    {
        $GROUP = new Group();
        $id = $request->id ?? 0;
        $user_id = $request->user_id ?? 0;
        $devices_id = $request->devices_id;

        $group = $GROUP->where('id', $id)->first();

        $group->devices()->sync($devices_id);

        $groups = $GROUP->where('user_id', $user_id)->with(['devices'])->withCount(['devices'])->get();

        return response()->json(['result' => 'OK', 'groups' => $groups]);
    }
}
