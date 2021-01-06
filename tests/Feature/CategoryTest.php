<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;
    /**
     * Categories list test..
     *
     * @return void
     */
    public function testCategoriesList()
    {
        Category::factory()->count(10)->create();

        $response = $this->get('/api/categories');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    public function testCategoriesStore()
    {
        $categoryName = $this->faker->text(50);

        $categoriesData = [
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'status' => $this->faker->randomElement([1, 2])
        ];

        $response = $this->post('/api/categories', $categoriesData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('categories', $categoriesData);
    }

    public function testCategoriesUpdate()
    {
        $category = Category::factory()->create();

        $categoryName = $this->faker->text(50);

        $categoryUpdatedData = [
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'status' => $this->faker->randomElement([1, 2])
        ];

        $response = $this->put(sprintf('/api/categories/%d', $category->id), $categoryUpdatedData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', $categoryUpdatedData);

        $this->assertDatabaseMissing('categories', $category->toArray());

    }

    public function testCategoriesDelete(){

        $category = Category::factory()->create();

        $response = $this->delete(sprintf('/api/categories/%d', $category->id));

        $response->assertStatus(200);

        $category->refresh();

        $this->assertDatabaseHas('categories', $category->toArray());
    }
}
