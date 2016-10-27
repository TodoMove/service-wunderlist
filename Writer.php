<?php

namespace TodoMove\Service\Wunderlist;

use GuzzleHttp\Client;

use TodoMove\Intercessor\Contracts\Service\Reader;
use TodoMove\Intercessor\Folder;
use TodoMove\Intercessor\Project;
use TodoMove\Intercessor\Repeat;
use TodoMove\Intercessor\Service\AbstractWriter;
use TodoMove\Intercessor\Tag;
use TodoMove\Intercessor\Task;

/**
 *
 * https://developer.wunderlist.com/documentation/
 *
 *
 * Need to create the folders last, as to create a folder you need to pass in list_ids returned from POST /lists
 *
 * Projects = Lists
 * Tasks = Tasks
 * Tasks->notes() = Notes (task_id or list_id)
 * Tags not supported....hmmmm.... We're going to lose them, or we'll have to have folders as contexts?
 * Tags are hash tags in the tasks title
 *
 * Might have to have projects as tasks, and tasks as subtasks?  Otherwise we'll lose project repeats and such
 * TODO: Consider projects as tasks and tasks as subtasks !!!   Subtasks can't have repeats or anything.  Tough one.  People will lose their project repeats
 * by using projects as lists, but that's the best I can do for now I think
 */

class Writer extends AbstractWriter
{
    protected $client;

    /**
     * @param string $clientId - From your Wunderlist App
     * @param string $token - OAuth token
     */
    public function __construct($clientId = '', $token = '')
    {
        $this->name('Wunderlist');

        $this->client = new Client([
            'base_uri' => 'https://a.wunderlist.com/api/v1/',
            'headers' => [
                'X-Access-Token' => $token,
                'X-Client-ID' => $clientId
            ],
        ]);
    }

    // TODO: How will we handle live updates of progress?  We'll need to mark each item as 'synced', then laravel echo can be used to say what's been synced?
    // TODO: Maybe we need an event/callback: $project->onSync(function($project) { echo::default('project.synced', $project); });

    /** @inheritdoc */
    public function syncFrom(Reader $reader)
    {
        $this->syncProjects($reader->projects());
        $this->syncFolders($reader->folders());
        $this->syncTasks($reader->tasks());
    }

    public function syncFolder(Folder $folder)
    {
        // TODO: Check for errors
        $listIds = [];
        foreach ($folder->projects() as $project) {
            $listIds[] = $project->meta('wunderlist-id');
        }

        if (empty($listIds)) { // We can't create a folder with no lists (projects to us for the minute)
            return true;
        }

        $response = json_decode($this->client->post('folders', [
            'json' => [
                'title' => $folder->name(),
                'list_ids' => array_filter($listIds), // Don't include projects without a wunderlist-id
            ]
        ])->getBody(), true);

        $folder->meta('wunderlist-id', $response['id']);

        return $response;

    }

    public function syncProject(Project $project)
    {
        // TODO: Check for errors

        $response = json_decode($this->client->post('lists', [
            'json' => [
                'title' => $project->name()
            ]
        ])->getBody(), true);

        $project->meta('wunderlist-id', $response['id']);

        return $response;
    }

    private function convertRepeatType($type) {
        switch($type) {
            case Repeat::DAY:
                $wunderlistType = 'day';
                break;
            case Repeat::WEEK:
                $wunderlistType = 'week';
                break;
            case Repeat::MONTH:
                $wunderlistType = 'month';
                break;
            case Repeat::YEAR:
                $wunderlistType = 'year';
                break;
            default:
                Throw new \Exception('Invalid repeat type');
        }

        return $wunderlistType;
    }

    public function syncTask(Task $task)
    {
        // TODO: Check for errors
        $data = [
            'list_id' => $task->project()->meta('wunderlist-id'),
            'title' => $task->title() . $this->taskTags($task),
            'starred' => $task->flagged(),
            'completed' => $task->completed(),
        ];

        if ($task->due()) {
            $data['due_date'] = $task->due()->format('Y-m-d');
        } elseif($task->defer()) {
            $data['due_date'] = $task->defer()->format('Y-m-d');
        }

        if ($task->repeat()) {
            $data['recurrence_type'] = $this->convertRepeatType($task->repeat()->type());
            $data['recurrence_count'] = $task->repeat()->interval();
        }

        $response = json_decode($this->client->post('tasks', [
            'json' => $data
        ])->getBody(), true);

        $task->meta('wunderlist-id', $response['id']);

        if (!empty($task->notes())) {
            $this->addNote($task);
        }

        return $response;
    }

    public function taskTags(Task $task)
    {
        $tags = '';
        /** @var Tag $tag */
        foreach ($task->tags() as $tag) {
            $tags .= ' #' . $tag->title();
        }

        Return $tags;
    }

    public function addNote(Task $task)
    {
        // TODO: Check for errors

        $response = json_decode($this->client->post('notes', [
            'json' => [
                'task_id' => $task->meta('wunderlist-id'),
                'content' => $task->notes(),
            ]
        ])->getBody(), true);

        return $response;
    }

    public function syncTag(Tag $tag)
    {
        // Not supported.  Tags in Wunderlist are just hashtags in the task title
        return;
    }


    protected function syncFolders(array $folders)
    {
        //TODO: Loop, and use $this->syncFolder(Folder $folder) to hit appropriate API's to add folders / throw exceptions.  Handling errors will be tough?
        //TODO: error checking, counting total and synced
        foreach ($folders as $folder) {
            $this->syncFolder($folder);
        }

        return $this;
    }

    protected function syncProjects(array $projects)
    {
        //TODO: Loop, and use $this->syncProject(Project $project) to hit appropriate API's to add folders / throw exceptions.  Handling errors will be tough?
        //TODO: error checking, counting total and synced
        foreach ($projects as $project) {
            $this->syncProject($project);
        }

        return $this;
    }

    protected function syncTags(array $tags)
    {
        return;
    }

    protected function syncTasks(array $tasks)
    {
        //TODO: Loop, and use $this->syncTag(Tag $tag) to hit appropriate API's to add folders / throw exceptions.  Handling errors will be tough?
        //TODO: error checking, counting total and synced
        foreach ($tasks as $task) {
            $this->syncTask($task);
        }

        return $this;
    }
}
