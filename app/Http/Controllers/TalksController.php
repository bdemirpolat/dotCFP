<?php

namespace App\Http\Controllers;

use App\Http\Requests\TalkRequest;
use App\Notifications\TalkApproved;
use App\Talk;
use App\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TalksController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $talks = Talk::with('user');

        if (!auth()->user()->inCommittee()) {
            $talks = $talks->speaker(auth()->id());
        } else {
            if ($request->get('status') === 'approved') {
                $talks = $talks->approved();
            }

            if ($request->get('order') === 'most-voted') {
                $talks = $talks->mostVoted();
            } else {
                $talks = $talks->latest();
            }
        }

        return response()->view('talks.index', [
            'talks' => $talks->paginate(10),
            'status' => $request->get('status') ?? null,
            'order' => $request->get('order') ?? null,
        ]);
    }

    /**
     * @param Talk $talk
     * @return Response
     */
    public function show(Talk $talk): Response
    {
        $talk->loadMissing(['user', 'comments']);

        return response()->view('talks.show', compact('talk'));
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function create(): Response
    {
        return response()->view('talks.create');
    }

    /**
     * @param TalkRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TalkRequest $request)
    {
        auth()->user()->talks()->create($request->only([
            'title', 'description', 'additional_information', 'duration', 'slide', 'is_favorite'
        ]));

        flash()->success('You have successfully added your talk!');

        return redirect()->route('talks.index');
    }

    /**
     * @param Talk $talk
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function edit(Talk $talk)
    {
        return response()->view('talks.edit', compact('talk'));
    }

    /**
     * @param TalkRequest $request
     * @param Talk $talk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TalkRequest $request, Talk $talk)
    {
        $talk->title = $request->get('title');
        $talk->description = $request->get('description');
        $talk->additional_information = $request->get('additional_information');
        $talk->duration = $request->get('duration', 40);
        $talk->slide = $request->get('slide');
        $talk->is_favorite = $request->get('is_favorite', 0);

        $talk->saveOrFail();

        flash()->success('You have successfully updated your talk!');

        return redirect()->route('talks.edit', $talk->slug);
    }

    /**
     * @param Talk $talk
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Talk $talk)
    {
        $talk->delete();

        flash()->success('You have successfully deleted your talk!');

        return redirect()->back();
    }

    /**
     * @param Request $request
     * @param Talk $talk
     * @return JsonResponse
     */
    public function voteAction(Request $request, Talk $talk): JsonResponse
    {
        $request->validate([
            'vote' => 'required|min:1|max:5'
        ]);

        Vote::updateOrCreate([
            'user_id' => auth()->id(),
            'talk_id' => $talk->id
        ], [
            'vote' => $request->get('vote')
        ]);

        $averageVote = Vote::whereTalkId($talk->id)->avg('vote');

        $talk->average_vote = $averageVote;

        if (!$talk->save()) {
            Log::emergency($request->route()->getName(), ['errors' => 'An error encountered while updating vote']);
        }

        return response()->json(['vote' => $talk->average_vote]);
    }

    /**
     * @param Request $request
     * @param Talk $talk
     * @return RedirectResponse
     */
    public function approveAction(Request $request, Talk $talk): RedirectResponse
    {
        $request->validate([
            'status' => 'required|boolean'
        ]);

        $talk->update(['status' => $request->get('status')]);

        if ($request->get('status') == 1) {
            $talk->user->notify(new TalkApproved($talk));
        }

        flash()->success('Talk status successfully changed!');

        return redirect()->route('talks.show', $talk->slug);
    }


    public function votes($talk)
    {
        $votes = Vote::with('user')->where('talk_id',$talk->id)->get();
        return view('talks.votes',compact('votes','talk'));

    }
}
