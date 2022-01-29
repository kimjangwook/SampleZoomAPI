<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Sample Zoom Application</title>

        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    </head>
    <body class="m-10">
        <h1 class="my-5 uppercase text-2xl">Sample Zoom Application</h1>

        <h2 class="text-xl mt-10">Create</h2>
        <form action="{{ route('create') }}" method="POST">
            @csrf
            <label for="topic" class="block mt-2 text-xs font-semibold text-gray-600 uppercase">topic</label>
            <input id="topic" type="text" name="topic" placeholder="Input the topic." value="{{ old('topic') ?? '' }}" class="block w-full p-3 mt-2 text-gray-700 bg-gray-200 appearance-none focus:outline-none focus:bg-gray-300 focus:shadow-inner" />
            @if ($errors->has("topic"))
                <div class="text-red-600 text-sm">{{ $errors->first("topic") }}</div>
            @endif

            <label for="start_time" class="block mt-2 text-xs font-semibold text-gray-600 uppercase">start time</label>
            <input id="start_time" type="datetime-local" name="start_time" placeholder="Input the topic." value="{{ old('start_time') ?? '' }}" class="block w-full p-3 mt-2 text-gray-700 bg-gray-200 appearance-none focus:outline-none focus:bg-gray-300 focus:shadow-inner" required />
            @if ($errors->has("start_time"))
                <div class="text-red-600 text-sm">{{ $errors->first("start_time") }}</div>
            @endif


            <label for="agenda" class="block mt-2 text-xs font-semibold text-gray-600 uppercase">agenda</label>
            <textarea id="agenda" type="text" name="agenda" placeholder="Input the agenda." class="block w-full p-3 mt-2 text-gray-700 bg-gray-200 appearance-none focus:outline-none focus:bg-gray-300 focus:shadow-inner">{{ old('agenda') ?? '' }}</textarea>
            @if ($errors->has("agenda"))
                <div class="text-red-600 text-sm">{{ $errors->first("agenda") }}</div>
            @endif

            <button type="submit" class="w-full py-3 mt-6 font-medium tracking-widest text-white uppercase bg-black shadow-lg focus:outline-none hover:bg-gray-900 hover:shadow-none">
                Create
            </button>
        </form>

        <h2 class="text-xl mt-10">Read</h2>
        <div class="w-full bg-white shadow-lg rounded-sm border border-gray-200">
            <div class="p-3">
                <div class="overflow-x-auto">
                    <table class="table-auto w-full">
                        <thead class="text-xs font-semibold uppercase text-gray-400 bg-gray-50">
                            <tr>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">TYPE</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">TOPIC</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">START TIME</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-left">DURATION</div>
                                </th>
                                <th class="p-2 whitespace-nowrap">
                                    <div class="font-semibold text-center">AGENDA</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            @foreach ($meetings as $item)
                            <tr>
                                <td class="p-2 whitespace-nowrap text-center">
                                    <div class="text-left font-xs text-purple-400">
                                        @if ($item['type'] === 1)
                                            INSTANT
                                        @elseif ($item['type'] === 2)
                                            SCHEDULE
                                        @elseif ($item['type'] === 3)
                                            RECURRING
                                        @elseif ($item['type'] === 4)
                                            FIXED_RECURRING_FIXED
                                        @endif
                                    </div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="font-medium text-gray-800"><a href="{{ $item['join_url'] }}" target="_blank">{{ $item['topic'] }}</a></div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left">{{ $item['start_time'] }}</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left font-medium text-green-500">{{ $item['duration'] }}</div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    <div class="text-left text-xs">{!! nl2br(e($item['agenda'])) !!}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
