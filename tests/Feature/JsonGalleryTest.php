<?php

namespace Tests\Feature;

use Tests\TestCase;

class JsonGalleryTest extends TestCase
{
    private function articles(): array
    {
        return json_decode(file_get_contents(public_path('articles.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_home_page_displays_all_six_articles_from_original_json(): void
    {
        $articles = $this->articles();
        $this->assertCount(6, $articles);

        $response = $this->get('/');
        $response->assertOk()->assertViewIs('home')->assertViewHas('articles');

        foreach ($articles as $index => $article) {
            $response->assertSeeText($article['name']);
            $response->assertSee('/galery/'.($index + 1));
            $response->assertSee($article['preview_image']);
            $response->assertSee($article['date']);
        }
    }

    public function test_preview_links_to_correct_full_image_for_each_article(): void
    {
        foreach ($this->articles() as $index => $article) {
            $this->get('/galery/'.($index + 1))
                ->assertOk()
                ->assertViewIs('galery')
                ->assertViewHas('article', fn (array $viewArticle) => $viewArticle['name'] === $article['name'])
                ->assertSee($article['full_image']);
        }
    }

    public function test_article_without_short_description_is_displayed(): void
    {
        $this->assertArrayNotHasKey('shortDesc', $this->articles()[1]);

        $this->get('/')->assertOk()->assertSee('Why do we use it?');
        $this->get('/galery/2')->assertOk()->assertSee('full.jpeg');
    }

    public function test_all_images_exist_and_are_real_jpegs(): void
    {
        foreach ($this->articles() as $article) {
            foreach (['preview_image', 'full_image'] as $field) {
                $path = public_path($article[$field]);
                $this->assertFileExists($path);
                $this->assertSame('image/jpeg', image_type_to_mime_type(exif_imagetype($path)));
            }
        }
    }

    public function test_unknown_gallery_id_returns_404(): void
    {
        $this->get('/galery/999')->assertNotFound();
        $this->get('/galery/0')->assertNotFound();
        $this->get('/galery/not-a-number')->assertNotFound();
    }

    public function test_other_pages_are_preserved(): void
    {
        $this->get('/about')->assertOk();
        $this->get('/contacts')->assertOk()->assertSee('@alinakashafutdinova');
    }
}
