<?php

namespace App\Http\Controllers;

use App\Events\MyEvent;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function msg(Request $request)
    {
        $name = $request->input('name');
        $msg = $request->input('message');

        return event(new MyEvent($name, $msg));
    }
}
