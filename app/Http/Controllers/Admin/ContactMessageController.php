<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Messages sent through the website's contact form (admin inbox)
class ContactMessageController extends Controller
{
    public function index(Request $request){
        $messages = ContactMessage::when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.cms.messages', compact('messages'));
    }

    public function show($message_id){
        $message = ContactMessage::findOrFail($message_id);
        if ($message->status === 'new') {
            $message->status = 'read';
            $message->save();
        }
        return view('admin.cms.message', compact('message'));
    }

    public function setStatus(Request $request, $message_id){
        $request->validate(['status' => ['required', Rule::in(['new', 'read', 'replied'])]]);
        $message = ContactMessage::findOrFail($message_id);
        $message->status = $request->status;
        $message->save();
        return redirect()->back()->with('success', 'Message marked as '.$request->status.'.');
    }
}
