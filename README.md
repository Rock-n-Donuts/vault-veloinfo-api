# API Veloinfo

## Requirements

- php 8.1
- Serveur de base de données (mariadb, mysql, postgres, etc.)

## Installer
"Pullez" le projet

Installer les dépendances via composer

`composer install`

ou

``wget https://getcomposer.org/composer.phar && php composer.phar install``


## Installer la BD

Importer le fichier schema.sql

## Configurer le .env

Copier le `.env.dist` sous `.env`

Remplir les informations des clés

## Rouler le projet

`php -S 127.0.0.1:8002`

### Routes

#### Authentication

```
POST /auth
Request body: {
    uuid: String,
}

Response body: {
    "user_id": integer,
    "token": string
}
```

#### Requêtes subséquentes

Les requêtes doivent être authentifiées avec le jeton reçu dans la requête précédente.

```
headers : {
    Authorization: Bearer %token
}
```

