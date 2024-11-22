<?php

namespace App\Controllers;

use App\Models\Playlist;
use App\Middleware\AuthMiddleware;
use MF\Controller\Action;
use MF\Model\Container;

class PlaylistController extends Action
{
    // Exibe o formulário para criar uma playlist
    public function criar()
    {
        AuthMiddleware::verificarNivelAcesso(2); // Apenas Gestores ou superiores

        $this->render('criar_playlist', 'layout2');
    }

    // Processa a criação da playlist
    public function salvar()
    {
        AuthMiddleware::verificarNivelAcesso(2); // Apenas Gestores ou superiores

        $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
        $id_usuario = $_SESSION['id'];

        if ($titulo) {
            $playlist = Container::getModel('Playlist');
            $playlist->__set('titulo', $titulo);
            $playlist->__set('descricao', $descricao);
            $playlist->__set('id_usuario', $id_usuario);

            $id_playlist = $playlist->criarPlaylist();

            $_SESSION['mensagem'] = 'Playlist criada com sucesso!';
            header("Location: /playlist/{$id_playlist}");
            exit;
        } else {
            $_SESSION['mensagem'] = 'Título é obrigatório.';
            header("Location: /playlist/criar");
            exit;
        }
    }

    // Exibe uma playlist específica com seus vídeos
    public function visualizar()
    {
        AuthMiddleware::isAuthenticated(); // Todos os usuários autenticados podem visualizar

        $id_playlist = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id_playlist) {
            $playlist = Container::getModel('Playlist');
            $videos = $playlist->obterVideosDaPlaylist($id_playlist);
            $this->view->videos = $videos;
            $this->view->playlist = $playlist;

            // Opcional: obter detalhes da playlist
            $query = "SELECT * FROM playlists WHERE id = :id";
            $stmt = $playlist->db->prepare($query);
            $stmt->bindValue(':id', $id_playlist);
            $stmt->execute();
            $detalhes_playlist = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->view->playlist = $detalhes_playlist;

            $this->render('visualizar_playlist', 'layout2');
        } else {
            header('Location: /apphome');
            exit;
        }
    }

    // Adiciona um vídeo a uma playlist
    public function adicionarVideo()
    {
        AuthMiddleware::verificarNivelAcesso(2); // Apenas Gestores ou superiores

        $id_playlist = filter_input(INPUT_POST, 'id_playlist', FILTER_VALIDATE_INT);
        $id_video = filter_input(INPUT_POST, 'id_video', FILTER_VALIDATE_INT);

        if ($id_playlist && $id_video) {
            $playlist = Container::getModel('Playlist');
            $playlist->adicionarVideo($id_playlist, $id_video);

            $_SESSION['mensagem'] = 'Vídeo adicionado à playlist com sucesso!';
            header("Location: /playlist/{$id_playlist}");
            exit;
        } else {
            $_SESSION['mensagem'] = 'Dados inválidos para adicionar vídeo.';
            header("Location: /playlist/{$id_playlist}");
            exit;
        }
    }

    // Lista todas as playlists para o usuário comum
    public function listar()
    {
        AuthMiddleware::isAuthenticated(); // Todos os usuários autenticados

        $id_usuario = $_SESSION['id'];
        $playlist = Container::getModel('Playlist');
        $playlists = $playlist->listarPlaylists($id_usuario);

        $this->view->playlists = $playlists;
        $this->render('listar_playlists', 'layout2');
    }
}