@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <input type="text" name="daterange" value="{{$start_date->format('m/d/Y')}} - {{$end_date->format('m/d/Y')}}" />
        </div>
        <div class="col-md-6">
            <button type="button" class="btn btn-primary" id="send-report">Send Report</button>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="alert alert-primary" role="alert">
                Current Week {{$start_date->format('m/d/Y')}} - {{$end_date->format('m/d/Y')}}
            </div>
            <table id="currentWeekDataTable" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th> Project Name </th>
                        <th> Task List </th>
                        <th> Description </th>
                        <th> HH:MM </th>
                        <th> Updated At </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeLogs as $log)
                    <tr>
                        <td> {{$log['project-name']}}</td>
                        <td> {{$log['todo-list-name']}}: <br />{{$log['todo-item-name']}}</td>
                        <td> {{$log['description']}} </td>
                        <td> {{$log['hours']}} : {{$log['minutes']}}</td>
                        <td> {{$log['dateUserPerspective']}}</td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <div class="alert alert-primary" role="alert">
                Next week
            </div>
            <table id="nextWeekDataTable" class="table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th> Project Name </th>
                        <th> Task Name </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($nextWeek as $log)
                    <tr>
                        <td> {{$log['project-name']}}</td>
                        <td> {{$log['content']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script defer>
    $(document).ready(function() {
        $('input[name="daterange"]').daterangepicker({
            opens: 'left'
        }, function(start, end, label) {
            console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
        });
        $('#currentWeekDataTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "All"]
            ],
            "order": [
                [4, "desc"]
            ]
        });
        $('#nextWeekDataTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "All"]
            ]
        });

        // Send Report
        $("#send-report").click(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                /* the route pointing to the post function */
                url: '/sendReport',
                type: 'POST',
                /* send the csrf-token and the input to the controller */
                data: {
                    _token: CSRF_TOKEN,
                    start_date: $('input[name="daterange"]').data('daterangepicker').startDate.format('YYYYMMDD'),
                    end_date: $('input[name="daterange"]').data('daterangepicker').endDate.format('YYYYMMDD')
                },
                dataType: 'JSON',
                /* remind that 'data' is the response of the AjaxController */
                success: function(data) {
                    $(".writeinfo").append(data.msg);
                }
            });
        });

    });
</script>
@endsection
