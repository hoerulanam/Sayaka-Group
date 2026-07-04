<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_news()
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->post(route('admin.news.store'), [
                'title' => 'Test News From Test',
                'content' => '<p>Content from test</p>',
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseHas('news', [
            'title' => 'Test News From Test',
        ]);
    }

    public function test_public_news_detail_can_be_opened_from_news_page()
    {
        $news = News::create([
            'title' => 'Detail Berita Publik',
            'slug' => 'detail-berita-publik',
            'content' => '<p>Isi lengkap berita publik.</p>',
            'published_at' => now(),
        ]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee(route('news.show', $news->slug), false);

        $this->get(route('news.show', $news->slug))
            ->assertOk()
            ->assertSee('Detail Berita Publik')
            ->assertSee('Isi lengkap berita publik.', false);
    }

    public function test_home_news_card_links_to_news_detail()
    {
        $news = News::create([
            'title' => 'Berita dari Beranda',
            'slug' => 'berita-dari-beranda',
            'content' => '<p>Konten berita dari beranda.</p>',
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('news.show', $news->slug), false);
    }
}
