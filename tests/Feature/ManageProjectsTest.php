<?php

namespace Tests\Feature;

use App\Models\User;
use App\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ManageProjectsTest extends TestCase
{
    use WithFaker,RefreshDatabase;

    /** @test */
    public function test_guests_cannot_manage_projects()
    {
        $project = Project::factory()->create();

        $this->get('/projects')->assertRedirect('login');

        $this->get('/projects/create')->assertRedirect('login');
        // This test asserts that a user must be logged in to view a project,
        // and is redirected to the login page if not.
        $this->get($project->path())->assertRedirect('login');

        $this->post('/projects', $project->toArray())->assertRedirect('login');
    }

    /** @test */
    public function test_a_user_can_create_a_project()
    {
        $this->withoutExceptionHandling();
        $this->actingAs(User::factory()->create());

        //Kiểm tra có thể gửi 1 yêu cầu get tới url localhost/project/create và nhận được status 200 không
        //Test này khẳng định form tạo project có thể được hiển thị
        //Chỉ kiểm tra form có tạo được không thành công hay không ,không cần kiểm tra request cho nó fill vào gì
        $this->get('/projects/create')->assertStatus(200);

        $attributes=[
            'title'=>$this->faker->sentence,
            'description'=>$this->faker->paragraph
        ];

        //Kiểm tra có thể gửi 1 yêu cầu post tới url localhost/project và insert các cột vào db không (tạo Modal Project trước khi test khai báo route)
        $this->post('/projects', $attributes)->assertRedirect('/projects');

        //khẳng định rằng cơ sở dữ liệu có một project bao gồm các thuộc tính là $attributes
        $this->assertDatabaseHas('projects', $attributes);

         //Khẳng định có thể gửi 1 yêu cầu get và thấy các thuộc tính đã tạo trên
        $this->get('/projects')->assertSee($attributes['title']);
    }

    public function test_an_authenticated_user_cannot_view_the_projects_of_others()
    {
        $this->be(User::factory()->create());

        // $this->withoutExceptionHandling();

        $project = Project::factory()->create();

        // This test attempts to access the show method of the ProjectController
        // without being authenticated. The test expects a 403 status code.
        $this->get($project->path())->assertStatus(403);

    }

    /** @test */
    public function test_a_project_requires_a_title()
    {

        //Tạo 1 User mới và đặt nó làm người dùng đã xác thực
        $this->actingAs(User::factory()->create());

        //Tạo các thuộc tính với factory, field title rỗng và không lưu vào db
        $attributes= Project::factory()->raw(['title'=>'']); //raw() trả về mảng || make() trả về 1 object || create() trả về object và save db

        //Gửi 1 yêu cầu post để lưu vào db, trường hợp nếu title chưa validate ở Controller sẻ báo lỗi do ta đã giả sử title rỗng
        $this->post('/projects', $attributes)->assertSessionHasErrors('title');
    }

    /** @test */
    public function test_a_user_can_view_their_project()
    {
        // Creates a user and logs them in.
        $this->be(User::factory()->create());

        $this->withoutExceptionHandling();
        $project = Project::factory()->create(['owner_id' => auth()->id()]);

        //Đảm bảo có thể gửi 1 yêu cầu get tới url /projects/{projectID} lúc này nó sẻ response html về
        //và assertSee sẻ đảm bảo ta có thể  thấy title và description trong phản hồi html (tức là cần phải tạo router ,controller và view)
        $this->get($project->path())
            ->assertSee($project->title)
            ->assertSee($project->description);
    }

    /** @test */
    public function test_a_project_requires_a_description()
    {
        $this->actingAs(User::factory()->create());

        $attributes=Project::factory()->raw(['description'=>'']);

        // Expect an error when the description is not provided.
        $this->post('/projects', $attributes)->assertSessionHasErrors('description');
    }


}
