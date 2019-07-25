<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \DateTime;
use App\Mail\SendReportEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Session;
use View;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $dtMin = new DateTime("last saturday"); // Edit
        $dtMin->modify('+1 day'); // Edit
        $dtMax = clone ($dtMin);
        $dtMax->modify('+6 days');
        $allUsers = \TeamWorkPm\Factory::build('people')->getAll();
        $request->session()->put('allUsers', json_decode($allUsers, true));
        return view('home', $this->getTeamWorkData($request, $dtMin, $dtMax));
    }

    /**
     * Send Report AJAX call
     *
     * @param Request $request
     *
     * @return void
     */
    public function sendReport(Request $request)
    {
        $loggedInUser =  $request->session()->get('user');
        $startDate = new Datetime($request->start_date);
        $endDate = new Datetime($request->end_date);
        $users = [];
        $allUsers =  $request->session()->get('allUsers');
        foreach ($allUsers as $user) {
            $users[$user['id']] = $user;
        }
        $newDocuments = [];
        $requestedUsers = $request->users;
        if (in_array('all', $request->users)) {
            $requestedUsers = array_keys($users);
        }
        foreach ($requestedUsers as $userId) {

            $teamWorkData = $this->getTeamWorkData($request, $startDate, $endDate, $userId);
            // Log::info($teamWorkData['timeLogs']);
            $timeArray = $nextWeekArray = [];
            foreach ($teamWorkData['timeLogs'] as $log) {
                $timeArray[$log['project-name']][$log['todo-list-name']][$log['todo-item-name']][] = $log['description'];
            }

            foreach ($teamWorkData['nextWeek'] as $log) {
                $nextWeekArray[$log['project-name']][] = $log['content'];
            }

            $generateDocumentName = 'Weekly_Report_' . $endDate->format('mdY') . '_' . $users[$userId]['first-name'] . '_' . $users[$userId]['last-name'];
            $generateDocument = $generateDocumentName . '.docx';
            $newDocuments[] = $generateDocument;
            // Creating the new document...
            $document = new \PhpOffice\PhpWord\TemplateProcessor(Storage::disk('send_report')->path('Weekly_Report_Template.docx'));

            $parser = new \HTMLtoOpenXML\Parser();
            $ooXml = $parser->fromHTML($this->array2ul($timeArray));
            $document->setValue('thisWeek', $ooXml);
            $document->setValue('nextWeek', $parser->fromHTML($this->array2ul($nextWeekArray)));
            $document->setValue('weekending', $endDate->format('m/d/Y'));
            $document->setValue('name', $users[$userId]['first-name'] . ' ' . $users[$userId]['last-name']);

            $document->saveAs(Storage::disk('send_report')->path($generateDocument));
        }
        $objDemo = new \stdClass();
        $objDemo->endDate = $endDate->format('m/d/Y');
        $objDemo->email = $loggedInUser['email-address'];
        $objDemo->sender = $loggedInUser['first-name'] . ' ' . $loggedInUser['last-name'];
        if (count($newDocuments) == 1) {
            $objDemo->subject = $generateDocumentName;
        } else {
            $objDemo->subject = 'Weekly_Report_' . $endDate->format('mdY');
        }

        $objDemo->attachment = $newDocuments;

        Mail::to($loggedInUser['email-address'])->send(new SendReportEmail($objDemo));
        foreach ($newDocuments as $document) {
            Storage::disk('send_report')->delete($document);
        }

        // Log::info($loggedInUser);
        Session::flash('success', 'Report has been successfully sent to ' . $loggedInUser['email-address']);
        return View::make('partials/flash-messages');
    }

    public function getTeamWorkData(Request $request, DateTime $startDate, DateTime $endDate, int $userId = null)
    {
        if ($userId === null) {
            $userId = $request->session()->get('user')['id'];
        }

        // Current Week
        $currentWeek = [
            'userid' => $userId,
            'FROMDATE' => $startDate->format('Ymd'),
            'TODATE' => $endDate->format('Ymd'),
            'sortby' => 'date',
            'sortorder' => 'DESC'
        ];
        $timeLogs = \TeamWorkPm\Factory::build('time')->getAll($currentWeek);
        $timeLogs = \json_decode($timeLogs, true);
        // Log::info($timeLogs);
        // GET Active Projects
        $activeProjects = \TeamWorkPm\Factory::build('project')->getActive();

        // Next Week
        $nextWeek = [
            // 'filter' => 'overdue',
            // 'sort' => 'duedate',
            // 'include' => 'taskListNames,projectNames',
            // 'responsible-party-id' => $userId,
            // 'startDate' => $startDate->format('Ymd'),
            'page' => '1',
            'pageSize' => '50',
            'useAllProjects' => 'true',
            'sort' => 'duedate',
            'sortOrder' => 'desc',
            'responsible-party-id' => $userId,
            'status' => 'active',
        ];
        $taskLists = $nextWeekTasks = [];
        foreach ($activeProjects as $project) {
            $tempTaskList = \TeamWorkPm\Factory::build('Task_List')->getByProject($project->id, $nextWeek);
            $taskLists[] = \json_decode($tempTaskList, true);
        }
        if (!empty($taskLists)) {
            foreach ($taskLists as $taskList) {
                foreach ($taskList as $taskl) {
                    foreach ($taskl['todo-items'] as $todoItems) {
                        if ($todoItems['status'] == 'new') {
                            $tempNextWeekTasks = [
                                'project-name' => $taskl['project-name'],
                                'content' =>  $todoItems['content']
                            ];
                            $nextWeekTasks[] = $tempNextWeekTasks;
                        }
                    }
                }
            }
        }

        return ['start_date' => $startDate, 'end_date' => $endDate, 'timeLogs' => $timeLogs, 'nextWeek' => $nextWeekTasks];
    }

    public function array2ul($array)
    {
        $out = "<ul>";
        foreach ($array as $key => $elem) {
            if (!is_array($elem)) {
                $out .= "<li><span>$elem</span></li>";
            } else $out .= "<li><span>$key</span>" . $this->array2ul($elem) . "</li>";
        }
        $out .= "</ul>";
        return $out;
    }
}
