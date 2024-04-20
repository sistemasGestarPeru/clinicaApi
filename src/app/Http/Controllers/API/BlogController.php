<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\ActualizarBlogRequest;
use App\Http\Requests\Blog\RegistroBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return BlogResource::collection(Blog::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistroBlogRequest $request)
    {
        return (new BlogResource(Blog::create($request->all())))
            ->additional(['msg' => 'Blog guardado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        return new BlogResource($blog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActualizarBlogRequest $request, Blog $blog)
    {
        $blog->update($request->all());
        return (new BlogResource($blog))
            ->additional(['msg' => 'Blog actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        $blog->delete();
        return (new BlogResource($blog))
            ->additional(['msg' => 'Blog eliminado correctamente']);
    }
}
