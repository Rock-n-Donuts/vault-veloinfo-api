# API Veloinfo

## Requirements

- php 8.1
- Serveur de base de données (mariadb, mysql, postgres, etc.)

## Installer
"Puller" le projet

Installer les dépendances via composer

`composer install`

ou

``wget https://getcomposer.org/composer.phar && php composer.phar install``


## Installer la BD

Importer le fichier schema.sql

## Configurer le .env

Copier le `.env.dist` sous `.env` et remplir

### Importation des données de la ville

```
php src/Jobs/CyclableJob.php
php src/Jobs/InfoNeigeJob.php
```

## Rouler le projet

`php -S 127.0.0.1:8002`

### Routes

#### Authentication

```
POST /auth
Request body: {
    uuid: String,
}

Response: {
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

#### Définitions

```
Troncon {
    coords: []<Long,Lat>
    id: uint,
    length: uint
    side_one_state: ?integer 
    side_two_state: ?integer
    trc_id: uint
    updated_at: date(Y-m-d H:i:s)
    winter: bool
    winter_protected: bool
}

Contribution {
    id: uint,
    created_at: date(Y-m-d H:i:s),
    issue_id: uint,
    comment: strin,
    user_id: uint,
    name: string,
    quality: ?integer,
    external_id: ?uint,
    is_external: bool,
    is_deleted: bool,
    replies: []Reply,
    coords: []<Long,Lat>,
    score: {
        positive: uint,
        negative: uint,
        last_vote: ?date(Y-m-d H:i:s),
        last_vote_date: ?date(Y-m-d H:i:s)
    },
    updated_at: date(Y-m-d H:i:s),
    image: {
        url: string,
        width: uint,
        height: uint,
        is_external: bool
    }
}
```
#### Récupération des données

```
GET /update
headers: {
    Authorization: Bearer %token
}

Query String: {
    update?: timestamp,
}

Response: {
    troncons: []Troncon,
    contributions: []Contribution,
    date: timestamp
}
```
###### Contributions

```
# long, lat
Location: float,float, 
```

```
POST /contribution
headers: {
    Authorization: Bearer %token
    Content-type: multipart/form-data
}

Request: {
    location: Location,
    comment: string,
    issue_id:  uint,
    name: string,
    photo: binary,
    quality: integer(-1|0|1)
}

Response: {
    contribution: Contribution
}
```