<?php

namespace App\Admin\Controllers;

use App\Imports\Import;
use App\Models\DefaultQuestion;
use App\Models\DefaultQuestionCategory;
use App\Models\Industry;
use App\Models\Role;
use App\Models\RoleCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DefaultQuestionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Стандартные вопросы';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DefaultQuestion());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('time', __('Time'));
        $grid->column('type', __('type'));
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        if (Admin::user()->isRole('administrator')) {
            $grid->tools(function ($tools) {
                $tools->append("
<form enctype='multipart/form-data' style='display: flex; align-items: center' method='post' action='" . route('admin.default-questions.import') . "'>
" . csrf_field() . "
<input name='import' accept='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel' type='file'><button type='submit' class='btn btn-default'>Imports</button>
</form>
");
            });
        }

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(DefaultQuestion::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('question', __('Question'));
        $show->field('time', __('Time'));
        $show->field('role_id', __('Role id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DefaultQuestion());
        config(['admin.upload.disk' => 'public']);
        $form->text('name');
        $form->summernote('question');
        $form->select('language')->options(['ru' => 'RU', 'en' => 'EN', 'de' => 'DE', 'es' => 'ES']);
        $form->select('type')->options(['VIDEO' => 'Video', 'TEXT' => 'Text', 'CODE' => 'Code']);
        $form->keyValue('keywords');

        $form->table('video', function ($table) {
            $table->file('video')->removable();
            $table->select('type')->options(['MAN' => 'MAN', 'WOMAN' => 'WOMAN', 'GAY' => 'GAY']);
        })->rules('required|min:1');
        $form->image('image')->removable();
        $form->time('time');
        $form->slider('positive')->options(['max' => 100, 'min' => 0, 'step' => 1]);
        $form->slider('negative')->options(['max' => 100, 'min' => 0, 'step' => 1]);
        $form->slider('neutral')->options(['max' => 100, 'min' => 0, 'step' => 1]);
        $form->switch('is_ai');
        //  $form->select('role_id', 'Role')->options(Role::all()->pluck('name','id'));
        $form->multipleSelect('roles', 'Roles')->options(function () {
            $roles = Role::all();
            return $roles->pluck('name', 'id');
        });

        return $form;
    }

    public function import(Request $request)
    {
        if (!$request->hasFile('import')) {
            return redirect()->back();
        }

        $import = new Import();

        $arr = Excel::toArray($import, $request->file('import'));

        $first = true;
        $ds = DIRECTORY_SEPARATOR;

        foreach ($arr['RU Questions'] as $question) {
            if ($first) {
                $first = false;
                continue;
            }

            if (!$question[1]) {
                continue;
            }

            if (DefaultQuestionCategory::where('name', $question[1])->where('language', 'ru')->first()) {
                continue;
            }

            $defaultQuestionCategory = new DefaultQuestionCategory();
            $defaultQuestionCategory->language = 'ru';
            $defaultQuestionCategory->name = $question[1];
            $defaultQuestionCategory->save();
        }

        $first = true;
        foreach ($arr['EN Questions'] as $question) {
            if ($first) {
                $first = false;
                continue;
            }

            if (!$question[1]) {
                continue;
            }

            if (DefaultQuestionCategory::where('name', $question[1])->where('language', 'ru')->first()) {
                continue;
            }

            $defaultQuestionCategory = new DefaultQuestionCategory();
            $defaultQuestionCategory->language = 'en';
            $defaultQuestionCategory->name = $question[1];
            $defaultQuestionCategory->save();
        }

        $first = true;
        $lastCategory = false;
        foreach ($arr['RU Questions'] as $question) {
            if ($first) {
                $first = false;
                continue;
            }

            if (!$question[2]) {
                continue;
            }

            if (DefaultQuestion::where('name', $question[2])->where('language', 'ru')->first()) {
                continue;
            }

            if ($question[1]) {
                $tempLastCategory = DefaultQuestionCategory::where('language', 'ru')->where('name', $question[1])->first();
                if ($tempLastCategory) {
                    $lastCategory = $tempLastCategory;
                }
            }


            $defaultQuestion = new DefaultQuestion();
            $keywords = [];
            if ($question[3]) {
                foreach (explode(';', $question[3]) as $keyword) {
                    $keywordParts = explode(':', $keyword);
                    if (isset($keywordParts[0]) && isset($keywordParts[1]) && (int)$keywordParts[1] != 0) {
                        $keywords += [$keywordParts[0] => $keywordParts[1]];
                    }
                }
            }

            $defaultQuestion->keywords = $keywords;
            $defaultQuestion->time = '00:02:00';
            $defaultQuestion->language = 'ru';
            $defaultQuestion->type = 'VIDEO';
            $defaultQuestion->is_ai = 1;
            $defaultQuestion->name = $question[2];
            $defaultQuestion->question = $question[2];
            $defaultQuestion->positive = $question[4];
            $defaultQuestion->neutral = $question[5];
            $defaultQuestion->negative = $question[6];
            if ($lastCategory) {
                $defaultQuestion->category_id = $lastCategory->id;
            }

            if (Storage::disk('local')->exists("public{$ds}import{$ds}ru{$ds}$question[0].mp4")) {
                $defaultQuestion->video = [
                    [
                        'video' => 'import/ru/' . $question[0] . '.mp4',
                        'type' => 'WOMAN'
                    ]
                ];
            }

            $defaultQuestion->save();
        }

        $first = true;
        $lastCategory = false;

        foreach ($arr['EN Questions'] as $question) {
            if ($first) {
                $first = false;
                continue;
            }

            if (!$question[2]) {
                continue;
            }

            if (DefaultQuestion::where('name', $question[2])->where('language', 'ru')->first()) {
                continue;
            }

            if ($question[1]) {
                $tempLastCategory = DefaultQuestionCategory::where('language', 'en')->where('name', $question[1])->first();
                if ($tempLastCategory) {
                    $lastCategory = $tempLastCategory;
                }
            }

            $defaultQuestion = new DefaultQuestion();
            $keywords = [];
            if ($question[3]) {
                foreach (explode(';', $question[3]) as $keyword) {
                    $keywordParts = explode(':', $keyword);
                    if (isset($keywordParts[0]) && isset($keywordParts[1]) && (int)$keywordParts[1] != 0) {
                        $keywords += [$keywordParts[0] => $keywordParts[1]];
                    }
                }
            }

            $defaultQuestion->keywords = $keywords;
            $defaultQuestion->time = '00:02:00';
            $defaultQuestion->language = 'en';
            $defaultQuestion->type = 'VIDEO';
            $defaultQuestion->is_ai = 1;
            $defaultQuestion->name = $question[2];
            $defaultQuestion->question = $question[2];
            $defaultQuestion->positive = $question[4];
            $defaultQuestion->neutral = $question[5];
            $defaultQuestion->negative = $question[6];
            if ($lastCategory) {
                $defaultQuestion->category_id = $lastCategory->id;
            }

            if (Storage::disk('local')->exists("public{$ds}import{$ds}en{$ds}$question[0].mp4")) {
                $defaultQuestion->video = [
                    [
                        'video' => 'import/en/' . $question[0] . '.mp4',
                        'type' => 'WOMAN'
                    ]
                ];
            }

            $defaultQuestion->save();
        }

        $first = true;
        foreach ($arr['ALL Jobs'] as $jobCategory) {
            if ($first) {
                $first = false;
                continue;
            }

            if ($jobCategory[1]) {
                if (!RoleCategory::where('name', $jobCategory[1])->where('language', 'en')->first()) {
                    $roleCategory = new RoleCategory();
                    $roleCategory->language = 'en';
                    $roleCategory->name = $jobCategory[1];
                    $roleCategory->save();
                }
            }

            if ($jobCategory[4]) {
                if (!RoleCategory::where('name', $jobCategory[4])->where('language', 'ru')->first()) {
                    $roleCategory = new RoleCategory();
                    $roleCategory->language = 'ru';
                    $roleCategory->name = $jobCategory[4];
                    $roleCategory->save();
                }
            }
        }

        $first = true;
        $industry = Industry::where('active', 1)->first();
        $lastRoleCategoryEn = false;
        $lastRoleCategoryRu = false;
        foreach ($arr['ALL Jobs'] as $job) {
            if ($first) {
                $first = false;
                continue;
            }

            if($job[1]) {
                $tempRoleLastCategory = RoleCategory::where('name',$job[1])->where('language','en')->first();
                if($tempRoleLastCategory) {
                    $lastRoleCategoryEn = $tempRoleLastCategory;
                }
            }

            if($job[4]) {
                $tempRoleLastCategory = RoleCategory::where('name',$job[4])->where('language','ru')->first();
                if($tempRoleLastCategory) {
                    $lastRoleCategoryRu = $tempRoleLastCategory;
                }
            }

            if ($job[2]) {
                if (!Role::where('name', $job[2])->where('language', 'en')->first()) {
                    $role = new Role();
                    $role->language = 'en';
                    $role->name = $job[2];
                    $role->active = 1;
                    if (Storage::disk('local')->exists("public{$ds}import{$ds}en{$ds}$job[0].html")) {
                        $text = Storage::disk('local')->get("public{$ds}import{$ds}en{$ds}$job[0].html");
                        $role->description = $text;
                    }
                    $role->industry_id = $industry->id;
                    if($lastRoleCategoryEn) {
                        $role->role_category_id = $lastRoleCategoryEn->id;
                    }
                    $role->save();
                    $questionsIdsXLSX = $job[3];
                    if ($questionsIdsXLSX) {
                        $questionsIdsXLSX = explode(',', $questionsIdsXLSX);
                        $role->questions()->detach();
                        foreach ($questionsIdsXLSX as $idXLSX) {
                            $idXLSX = trim($idXLSX);
                            if (isset($arr['RU Questions'][$idXLSX]) && $arr['EN Questions'][$idXLSX][2]) {
                                $ourQuestion = DefaultQuestion::where('language', 'en')->where('name', $arr['EN Questions'][$idXLSX][2])->first();
                                if ($ourQuestion) {
                                    $role->questions()->attach($ourQuestion->id);
                                }
                            }
                        }
                    }
                }
            }

            if ($job[5]) {
                if (!Role::where('name', $job[5])->where('language', 'ru')->first()) {
                    $role = new Role();
                    $role->language = 'ru';
                    $role->name = $job[5];
                    $role->active = 1;
                    if (Storage::disk('local')->exists("public{$ds}import{$ds}ru{$ds}$job[0].html")) {
                        $text = Storage::disk('local')->get("public{$ds}import{$ds}ru{$ds}$job[0].html");
                        $role->description = $text;
                    }
                    $role->industry_id = $industry->id;
                    if($lastRoleCategoryRu) {
                        $role->role_category_id = $lastRoleCategoryRu->id;
                    }
                    $role->save();
                    $questionsIdsXLSX = $job[6];
                    if ($questionsIdsXLSX) {
                        $questionsIdsXLSX = explode(',', $questionsIdsXLSX);
                        $role->questions()->detach();
                        foreach ($questionsIdsXLSX as $idXLSX) {
                            $idXLSX = trim($idXLSX);
                            if (isset($arr['RU Questions'][$idXLSX]) && $arr['RU Questions'][$idXLSX][2]) {
                                $ourQuestion = DefaultQuestion::where('language', 'ru')->where('name', $arr['RU Questions'][$idXLSX][2])->first();
                                if ($ourQuestion) {
                                    $role->questions()->attach($ourQuestion->id);
                                }
                            }
                        }
                    }
                }
            }
        }

        return redirect()->back();
    }
}
