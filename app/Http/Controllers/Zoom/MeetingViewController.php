<?php

namespace App\Http\Controllers\Zoom;

use App\Http\Controllers\Controller;
use App\Traits\ZoomJWT;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingViewController extends Controller
{
    use ZoomJWT;

    # Type of meetings
    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;

    /**
     * Return list of meetings.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $path = 'users/me/meetings';
        $response = $this->zoomGet($path);

        $data = json_decode($response->body(), true);
        $data['meetings'] = collect($data['meetings'])->map(function ($meet) {
            $meet['start_time'] = Carbon::parse($meet['start_time'])->format('Y/m/d H:i:s');
            $meet['created_at'] = Carbon::parse($meet['created_at'])->format('Y/m/d H:i:s');
            return $meet;
        });

        $total_records = $data['total_records'];
        $next_page_token = $data['next_page_token'];
        $meetings = $data['meetings'];

        return view('index', compact('total_records', 'next_page_token', 'meetings'));
    }

    /**
     * Create the meeting room.
     *
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'topic' => 'required|string',
           'start_time' => 'required|date',
           'agenda' => 'string|nullable',
        ]);

        $data = request()->validate([
            'topic' => 'required|string',
            'start_time' => 'required|date',
            'agenda' => 'string|nullable',
        ]);

        $path = 'users/me/meetings';
        $response = $this->zoomPost($path, [
            'topic' => $data['topic'],
            'type' => self::MEETING_TYPE_SCHEDULE,
            'start_time' => $this->toZoomTimeFormat($data['start_time']),
            'duration' => 30,
            'agenda' => $data['agenda'],
            'settings' => [
                'host_video' => false,
                'participant_video' => false,
                'waiting_room' => true,
            ]
        ]);

        return redirect(route('index'));
    }
}
