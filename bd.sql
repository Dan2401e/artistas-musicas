CREATE DATABASE IF NOT EXISTS sistema_musical_v2;
USE sistema_musical_v2;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS artistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    genero VARCHAR(50),
    pais VARCHAR(50),
    imagem VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS musicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artista_id INT,
    titulo VARCHAR(100) NOT NULL,
    album VARCHAR(100),
    genero VARCHAR(50),
    youtube_link VARCHAR(255),
    letra TEXT,
    imagem_capa VARCHAR(255),
    FOREIGN KEY (artista_id) REFERENCES artistas(id)
);

INSERT INTO usuarios (usuario, senha)
VALUES ('admin', '$2y$12$zw1c2aDgt4HHnAzXc3g3dOV6Mn96Dm2UaSIketIhYe1xmtuaJG6qa')
ON DUPLICATE KEY UPDATE senha = VALUES(senha);
