<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \DateTime;
use App\Mail\SendReportEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        return view('home', $this->getTeamWorkData($request, $dtMin, $dtMax));
    }

    /**
     * Send Report AJAX call
     *
     * @param Request $request
     * @return void
     */
    public function sendReport(Request $request)
    {
        $user =  $request->session()->get('user');
        $startDate = new Datetime($request->start_date);
        $endDate = new Datetime($request->end_date);
        $response = array(
            'status' => 'success',
            'start_date' => $startDate,
            'end_date' => $endDate,
        );
        $teamWorkData = $this->getTeamWorkData($request, $startDate, $endDate);
        // Log::info($teamWorkData['timeLogs']);
        $timeArray = $nextWeekArray = [];
        foreach ($teamWorkData['timeLogs'] as $log) {
            $timeArray[$log['project-name']][$log['todo-list-name']][$log['todo-item-name']][] = $log['description'];
        }

        foreach ($teamWorkData['nextWeek'] as $log) {
            $nextWeekArray[$log['project-name']][] = $log['content'];
        }

        $generateDocumentName = 'Weekly_Report_' . $endDate->format('mdY') . '_' . $user['first-name'] . '_' . $user['last-name'];
        $generateDocument = $generateDocumentName . '.docx';

        // Creating the new document...
        $document = new \PhpOffice\PhpWord\TemplateProcessor(Storage::disk('send_report')->path('Weekly_Report_Template.docx'));

        $parser = new \HTMLtoOpenXML\Parser();
        $ooXml = $parser->fromHTML($this->array2ul($timeArray));
        $document->setValue('thisWeek', $ooXml);
        $document->setValue('nextWeek', $parser->fromHTML($this->array2ul($nextWeekArray)));
        $document->setValue('weekending', $endDate->format('m/d/Y'));
        $document->setValue('name', $user['first-name'] . ' ' . $user['last-name']);


        $document->saveAs(Storage::disk('send_report')->path($generateDocument));
        $objDemo = new \stdClass();
        $objDemo->endDate = $endDate->format('m/d/Y');
        $objDemo->email = $user['email-address'];
        $objDemo->sender = $user['first-name'] . ' ' . $user['last-name'];
        $objDemo->subject = $generateDocumentName;
        $objDemo->attachment = $generateDocument;

        Mail::to($user['email-address'])->send(new SendReportEmail($objDemo));
        Storage::disk('send_report')->delete($generateDocument);
        return response()->json($response);
    }

    public function getTeamWorkData(Request $request, DateTime $startDate, DateTime $endData = null)
    {
        $userId = $request->session()->get('user')['id'];
        // Current Week
        $currentWeek = [
            'userid' => $userId,
            'FROMDATE' => $startDate->format('Ymd'),
            'TODATE' => $endData->format('Ymd'),
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
        return ['start_date' => $startDate, 'end_date' => $endData, 'timeLogs' => $timeLogs, 'nextWeek' => $nextWeekTasks];
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
