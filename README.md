
# Manage Zoom Meeting Rooms through Zoom API with Laravel

This tutorial is for who want to build an app that provides **server_to_server** interaction with Zoom APIs to manage your account.


## Build an Zoom Application

1. Access [Zoom marketplace](https://marketplace.zoom.us/)

2. Sign in

3. Click `Develop` button on header and select `Build App` menu.

4. Choose the `JWT` and create application with the app name what you want.

5. Input required information and click `Continue` until your app will be activated. Don't forget to remember your credentials. It's used for API calling.

![Build App](zoom_build_app_01.png)

![JWT type](zoom_build_app_02.png)

![Create application](zoom_build_app_03.png)

![Your app is activated](zoom_build_app_04.png)

## Create Project

```sh
$ laravel new SampleZoomAPI
$ cd SampleZoomAPI
```


## Test API Endpoint

Now, we should modify api routes file to check our setting was correct.

```php
# /routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'result' => true,
    ];
});
```

Access [http://localhost:8000/api](http://localhost:8000/api) and check our setting was set correctly. If your source code work correctly, let's start to configure some setting to use Zoom APIs.

## Add libraries and settings

To use **server_to_server** Zoom API, we need to generate JWT. And to authenticate, we need to communicate with Zoom.

So I add `firebase/php-jwt` and `guzzlehttp/guzzle` libraries to my project.

```sh
$ composer require firebase/php-jwt
$ composer require guzzlehttp/guzzle
```

Additionally we need to modify `.env` files to set the api url & key & secret of the zoom.

```conf
ZOOM_API_URL="https://api.zoom.us/v2/"
ZOOM_API_KEY="INPUT_YOUR_ZOOM_API_KEY"
ZOOM_API_SECRET="INPUT_YOUR_ZOOM_API_SECRET"
```

Before make some endpoints, think about what we want to do.

We want to `GET` list of meetings, `GET` information of the meeting, `UPDATE` meeting information, `DELETE` meeting. The 4 types of requests are required, right?

So, I'll make traits that include some common methods.


## Make traits to use easily common methods

```php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ZoomJWT
{
    // I'll add some method
}
```

The first method of ZoomJWT trait is `generateZoomToken`. To use Zoom API, we have to include JWT token on the request.

Using `firebase/php-jwt` library, it's very simple to generate JWT. It returns string type's encoded JWT token.

```php
# /app/Traits/ZoomJWT.php

private function generateZoomToken()
{
    $key = env('ZOOM_API_KEY', '');
    $secret = env('ZOOM_API_SECRET', '');
    $payload = [
        'iss' => $key,
        'exp' => strtotime('+1 minute'),
    ];
    return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
}
```

Second, we will make method for get `ZOOM_API_URL` environment variable.

```php
# /app/Traits/ZoomJWT.php

private function retrieveZoomUrl()
{
    return env('ZOOM_API_URL', '');
}
```

Third method returns `Request`. We'll use this `Request` instance to send request to Zoom API end point.

```php
# /app/Traits/ZoomJWT.php

private function zoomRequest()
{
    $jwt = $this->generateZoomToken();
    return \Illuminate\Support\Facades\Http::withHeaders([
        'authorization' => 'Bearer ' . $jwt,
        'content-type' => 'application/json',
    ]);
}
```

These methods return `Response` that is used to execute `GET/POST/PATCH/DELETE` request.

```php
# /app/Traits/ZoomJWT.php

public function zoomGet(string $path, array $query = [])
{
    $url = $this->retrieveZoomUrl();
    $request = $this->zoomRequest();
    return $request->get($url . $path, $query);
}

public function zoomPost(string $path, array $body = [])
{
    $url = $this->retrieveZoomUrl();
    $request = $this->zoomRequest();
    return $request->post($url . $path, $body);
}

public function zoomPatch(string $path, array $body = [])
{
    $url = $this->retrieveZoomUrl();
    $request = $this->zoomRequest();
    return $request->patch($url . $path, $body);
}

public function zoomDelete(string $path, array $body = [])
{
    $url = $this->retrieveZoomUrl();
    $request = $this->zoomRequest();
    return $request->delete($url . $path, $body);
}
```

The last methods are used for generate new format of datetime string.

I'll use `<input type="datetime-local">` format to set start time of meeting, but that form just get `yyyy-MM-dd\THH:mm` format of data.

To use Zoom API, we should change time format to `yyyy-MM-dd\THH:mm:ss`. That's why I'm creating these 2 methods.

```php
public function toZoomTimeFormat(string $dateTime)
{
    try {
        $date = new \DateTime($dateTime);
        return $date->format('Y-m-d\TH:i:s');
    } catch(\Exception $e) {
        Log::error('ZoomJWT->toZoomTimeFormat : ' . $e->getMessage());
        return '';
    }
}

public function toUnixTimeStamp(string $dateTime, string $timezone)
{
    try {
        $date = new \DateTime($dateTime, new \DateTimeZone($timezone));
        return $date->getTimestamp();
    } catch (\Exception $e) {
        Log::error('ZoomJWT->toUnixTimeStamp : ' . $e->getMessage());
        return '';
    }
}
```


## Make API Endpoints

I'll create 5 end point.

```php
# /routes/api.php

// Get list of meetings.
Route::get('/meetings', 'Zoom\MeetingController@list');

// Create meeting room using topic, agenda, start_time.
Route::post('/meetings', 'Zoom\MeetingController@create');

// Get information of the meeting room by ID.
Route::get('/meetings/{id}', 'Zoom\MeetingController@get')->where('id', '[0-9]+');
Route::patch('/meetings/{id}', 'Zoom\MeetingController@update')->where('id', '[0-9]+');
Route::delete('/meetings/{id}', 'Zoom\MeetingController@delete')->where('id', '[0-9]+');
```

Meeting rooms of the Zoom have ID that is composed of `integer` type of data.


## Make a controller

```sh
$ php artisan make:controller Zoom/MeetingController
```

In this time, I'll controll meeting rooms simply because it's just test application.

```php
# /app/Http/Controllers/Zoom/MeetingController.php

namespace App\Http\Controllers\Zoom;

use App\Http\Controllers\Controller;
use App\Traits\ZoomJWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    use ZoomJWT;

    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;

    public function list(Request $request) { /**/ }
    public function create(Request $request) { /**/ }
    public function get(Request $request, string $id) { /**/ }
    public function update(Request $request, string $id) { /**/ }
    public function delete(Request $request, string $id) { /**/ }
}

```

This is a declaration section. 

* `use`: to use traits

* `const xxx = <numeric>`: Zoom supports 4 types of Meeting. In this time, we'll manage `MEETING_TYPE_SCHEDULE` type of Meeting because it's the default type.

```php
use ZoomJWT;

const MEETING_TYPE_INSTANT = 1;
const MEETING_TYPE_SCHEDULE = 2;
const MEETING_TYPE_RECURRING = 3;
const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;
```

This section gets list of meeting rooms and their information.

To handle `start_time` with `timezone` easily, I add `start_at` property in the information.

```php
public function list(Request $request)
{
    $path = 'users/me/meetings';
    $response = $this->zoomGet($path);

    $data = json_decode($response->body(), true);
    $data['meetings'] = array_map(function (&$m) {
        $m['start_at'] = $this->toUnixTimeStamp($m['start_time'], $m['timezone']);
        return $m;
    }, $data['meetings']);

    return [
        'success' => $response->ok(),
        'data' => $data,
    ];
}
```

To create the meeting room, actually we need various properties, but in this time, we just use `topic`, `agenda` and `start_time`.

If you want to set more various options to create meeting, please read [Zoom API Reference](https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingcreate).

```php
public function create(Request $request)
{
    $validator = Validator::make($request->all(), [
        'topic' => 'required|string',
        'start_time' => 'required|date',
        'agenda' => 'string|nullable',
    ]);
    
    if ($validator->fails()) {
        return [
            'success' => false,
            'data' => $validator->errors(),
        ];
    }
    $data = $validator->validated();

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


    return [
        'success' => $response->status() === 201,
        'data' => json_decode($response->body(), true),
    ];
}
```

Next is get a meeting information section. Using meeting room id, we can get meeting room information easily.

```php
public function get(Request $request, string $id)
{
    $path = 'meetings/' . $id;
    $response = $this->zoomGet($path);

    $data = json_decode($response->body(), true);
    if ($response->ok()) {
        $data['start_at'] = $this->toUnixTimeStamp($data['start_time'], $data['timezone']);
    }

    return [
        'success' => $response->ok(),
        'data' => $data,
    ];
}
```

The update section is almost same as create section.

```php
public function update(Request $request, string $id)
{
    $validator = Validator::make($request->all(), [
        'topic' => 'required|string',
        'start_time' => 'required|date',
        'agenda' => 'string|nullable',
    ]);

    if ($validator->fails()) {
        return [
            'success' => false,
            'data' => $validator->errors(),
        ];
    }
    $data = $validator->validated();

    $path = 'meetings/' . $id;
    $response = $this->zoomPatch($path, [
        'topic' => $data['topic'],
        'type' => self::MEETING_TYPE_SCHEDULE,
        'start_time' => (new \DateTime($data['start_time']))->format('Y-m-d\TH:i:s'),
        'duration' => 30,
        'agenda' => $data['agenda'],
        'settings' => [
            'host_video' => false,
            'participant_video' => false,
            'waiting_room' => true,
        ]
    ]);

    return [
        'success' => $response->status() === 204,
        'data' => json_decode($response->body(), true),
    ];
}
```

The last section is delete section. It uses when user want to delete a specific meeting.


```php
public function delete(Request $request, string $id)
{
    $path = 'meetings/' . $id;
    $response = $this->zoomDelete($path);

    return [
        'success' => $response->status() === 204,
        'data' => json_decode($response->body(), true),
    ];
}
```

## API Document

You can check my [API Document](https://documenter.getpostman.com/view/5798803/T1DngxJs?version=latest) made by Postman.
