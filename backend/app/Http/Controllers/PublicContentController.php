<?php

namespace App\Http\Controllers;

use App\Services\PublicArticleData;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function __invoke(Request $request, PublicArticleData $data)
    {
        $edition = $request->query('edition', 'global');
        abort_unless(in_array($edition, config('africaranked.editions'), true), 404);

        return response()->json(['articles' => $data->catalog($edition)]);
    }
}
