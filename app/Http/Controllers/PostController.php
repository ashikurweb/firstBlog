<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware('auth', except: ['index', 'show']),
        ];
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::latest()->paginate(6);

        return view('posts.index', ['posts' => $posts]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate
        $fields = $request->validate([
            'title' => ['required','max:255'],
            'body' => ['required'],
            'image' => ['nullable', 'file', 'max:9000', 'mimes:jpg,jpeg,png'],
        ]);

        // Store image if exits
        $path = null;
        if ($request->hasFile('image')) {
            $path = Storage::disk('public')->put('posts_images', $request->image);
        }


        // Create a new post

        Auth::user()->posts()->create([
            'title' => $fields['title'],
            'body' => $fields['body'],
            'image' => $path
        ]);

        // Redirect to dashboard

        return back()->with('success', 'Your post has been created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return view('posts.show', ['post' => $post]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        Gate::authorize('modify', $post);
        return view('posts.edit', ['post' => $post]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // Authorize to action modify
        Gate::authorize('modify', $post);

        // Validate
        $fields = $request->validate([
            'title' => ['required','max:255'],
            'body' => ['required'],
            'image' => ['nullable', 'file', 'max:9000', 'mimes:jpg,jpeg,png'],
        ]);

        // Store image if exits
        $path = $post->image ?? null;
        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $path = Storage::disk('public')->put('posts_images', $request->image);
        }

        // Update Post

        $post->update([
            'title' => $fields['title'],
            'body' => $fields['body'],
            'image' => $path
        ]);

        // Redirect to dashboard

        return redirect()->route('dashboard')->with('success', 'Your post has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Authorize to action modify
        Gate::authorize('modify', $post);

        //  Delete post image if exits
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        // Delete Post
        $post->delete();

        // Redirect to dashboard
        // return back()->with('delete', 'Your post has been deleted!');
        return back()->with('delete', 'Post deleted successfully!');
    }
}
