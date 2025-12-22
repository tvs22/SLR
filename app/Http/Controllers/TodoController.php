<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TodoController extends Controller
{
    /**
     * Fetch a todo from the jsonplaceholder API.
     *
     * @return \Illuminate\Http\Response
     */
    public function fetchTodo()
    {
        $response = Http::get('https://jsonplaceholder.typicode.com/todos/1');
        return $response->json();
    }
}
