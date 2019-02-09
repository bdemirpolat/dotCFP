@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-list"></i>
                            {{ $talk }} Votes
                        </h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover">
                                <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Vote</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($votes as $vote)
                                <tr>
                                   <td>{{$vote->user->name}}</td>
                                   <td>{{$vote->vote}}</td>
                                    @if($vote->updated_at)
                                        <td>{{$vote->updated_at}}</td>
                                    @else
                                        <td>{{$vote->created_at}}</td>
                                    @endif
                                </tr>

                                @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('users.modal')

@endsection
