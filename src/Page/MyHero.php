<?php

namespace De\Idrinth\Travian\Page;

use De\Idrinth\Travian\Twig;
use De\Idrinth\Travian\World;
use PDO;

class MyHero
{
    private $database;
    private $twig;
    public function __construct(PDO $database, Twig $twig)
    {
        $this->database = $database;
        $this->twig = $twig;
    }
    public function run(array $post, $id = '', $key=''): void
    {
        if (($_SESSION['id'] ?? 0) === 0) {
            header('Location: /login', true, 303);
            $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
            return;
        }
        if (isset($post['delete'])) {
            $this->database
                ->prepare('DELETE FROM my_hero WHERE aid=:aid AND user=:user')
                ->execute([':user' =>$_SESSION['id'], ':aid' => $post['delete']]);
        } elseif (isset($post['fighting_strength']) && isset($post['off_bonus']) && isset($post['def_bonus']) && isset($post['resources'])) {
            $this->database
                ->prepare('UPDATE my_hero SET boot_bonus=:bb,standard_bonus=:sb,fighting_strength=:fs, off_bonus=:ob, deff_bonus=:db, resources=:r WHERE aid=:aid AND user=:user')
                ->execute([':sb' => $post['standard_bonus'],':bb' => $post['boot_bonus'],':user' =>$_SESSION['id'], ':aid' => $post['aid'], ':fs' => $post['fighting_strength'], ':ob' => $post['off_bonus'], ':db' => $post['def_bonus'], ':r' => $post['resources']]);
        } elseif (isset($post['world'])) {
            $stmt = $this->database->prepare('SELECT * FROM my_hero WHERE `user`=:user AND world=:world');
            $stmt->execute([':user' =>$_SESSION['id'], ':world' => World::toWorld($post['world'])]);
            if (false === $stmt->fetchColumn()) {
                $this->database
                    ->prepare('INSERT INTO my_hero (user, world, fighting_strength, off_bonus, deff_bonus, resources) VALUES (:user, :world, 0,0,0,4)')
                    ->execute([':user' =>$_SESSION['id'], ':world' => $post['world']]);
            }
        }
        $stmt = $this->database->prepare('SELECT * FROM my_hero WHERE user=:user ORDER BY world DESC');
        $stmt->execute([':user' => $_SESSION['id']]);        
        $data['heroes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->twig->display('my-hero.twig', $data);
    }
}
