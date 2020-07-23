<?php


Route::get('metadata', function(\Illuminate\Http\Request $request){
    if (! $request->hasValidSignature()) {
        abort(401);
    }

    $xml = optional(\App\Audit::find($request->get('audit')))->getSsoMetadata();
    $response = \Illuminate\Http\Response::create($xml, 200);
    $response->header('Content-Type', 'text/xml');
    $response->header('Cache-Control', 'public');
    $response->header('Content-Description', 'File Transfer');
    $response->header('Content-Transfer-Encoding', 'binary');
    return $response;
})->name('sso.metadata');
