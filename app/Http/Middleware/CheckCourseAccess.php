<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCourseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $courseId = $request->route('courseId') ?? $request->route('course')?->id;

        if (! $courseId) {
            return $next($request);
        }

        $course = \App\Models\Course::find($courseId);

        if (! $course || ! $user->canViewCourse($course)) {
            abort(403, 'You do not have access to this course');
        }

        return $next($request);
    }
}
