<?php

require __DIR__ . '/../vendor/autoload.php';

const APP_PATH = __DIR__ . '/../';

if (!file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../'); // server, set file out of webroot
}
$dotenv->load();

use Rockndonuts\Hackqc\Models\Contribution;
use Rockndonuts\Hackqc\Models\ContributionReply;
use Rockndonuts\Hackqc\Transformers\ContributionTransformer;
use Rockndonuts\Hackqc\FileHelper;


?>
<style>
    table {
        border-collapse: collapse;
        width: 80%;
        margin: 0 auto;
        text-align: center;
    }

    tr {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border: 1px solid black;
    }

    tr:hover {
        background-color: #ccc;
    }
</style>


<a href="https://api.veloinfo.ca/admin">Afficher toutes les contributions</a>
<a href="?withoutDeleted=true">Afficher les contributions non-supprimées</a>

<br><br>
<?php
$c = new Contribution;
$cr = new ContributionReply;

if (isset($_GET['delete'])) {
    $c->update($_GET['delete'], ['is_deleted' => 1]);
    echo "Contribution cachée";
}


if (isset($_GET['undelete'])) {
    $c->update($_GET['undelete'], ['is_deleted' => 0]);
    echo "Contribution ré-affichée";
}


if (isset($_GET['see'])) {


    if (isset($_GET['deleteReply'])) {
        $cr->update($_GET['deleteReply'], ['is_deleted' => 1]);
        echo "Commentaire caché";
    }

    $contrib = $c->findOneBy(['id' => $_GET['see']]);
    $t = new ContributionTransformer;
    $contrib = $t->transform($contrib);
?>
    <h1><?php echo $contrib['name'] ?></h1>
    <p><?php echo $contrib['comment']; ?></p>
    <h3>Réponses</h3>
    <?php
    if (empty($contrib['replies'])) {
        echo "Aucune réponse";
    } else {
    ?>
        <table>
            <thead>
                <th>Message</th>
                <th>Caché?</th>
                <th>Actions</th>
            </thead>
            <?php
            foreach ($contrib['replies'] as $reply) {
            ?>
                <tr>
                    <td><?php echo $reply['message']; ?></td>
                    <td><?php echo $reply['is_deleted']; ?></td>
                    <td><a href="?see=<?php echo $_GET['see']; ?>&deleteReply=<?php echo $reply['id']; ?>">Supprimer</a></td>
                </tr>
        <?php
            }
        }
        ?>
    <?php
    exit;
}

$fh = new FileHelper;
$all = $c->findBy(['issue_id' => 1]);
$all = array_reverse($all);
    ?>
    <table>
        <thead>
            <th>Date</th>
            <th>Nom</th>
            <th>Message</th>
            <th>Image</th>
            <th>Qualité</th>
            <th>Cachée?</th>
            <th>Arrondissement</th>
            <th>Actions</th>
        </thead>
        <?php

        foreach ($all as $single) {
            if (!empty($_GET['withoutDeleted'])) {
                if ($single['is_deleted'] == 1) {
                    continue;
                }
            }
            $replies = $cr->findBy(['contribution_id' => $single['id']]);
            $repliesNb = count($replies);
            $img = "";
            if (!is_null($single['photo_path'])) {
                $img = "https://api.veloinfo.ca/uploads/" . $single['photo_path'];
            }

            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $single['created_at']);
            $hour = \DateTime::createFromFormat('Y-m-d H:i:s', $single['created_at'], new \DateTimeZone('America/New_York'));
            $formatter = new IntlDateFormatter(
                'fr_CA',
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::SHORT,
                new \DateTimeZone('America/New_York')
            );
        ?>
            <tr>
                <td><?php echo $formatter->format($date); ?></td>
                <td><?php echo $single['name']; ?></td>
                <td style="width:450px"><?php echo $single['comment']; ?></td>
                <td>
                    <?php if ($img !== "") { ?>
                        <a target="_blank" href="<?php echo $img; ?>"><img width="50" height="50" src="<?php echo $img;  ?>"></a>
                    <?php } ?>
                </td>
                <td><?php echo $single['quality']; ?></td>
                <td><?php echo $single['is_deleted']; ?></td>
                <td><?php echo $single['borough_name']; ?></td>
                <td>
                    <?php if ($single['is_deleted'] == '0') { ?>
                        <a href="?delete=<?php echo $single['id']; ?>">Supprimer</a><br /><br />
                    <?php } else { ?>
                        <a href="?undelete=<?php echo $single['id']; ?>">Ré-afficher</a><br /><br />
                    <?php } ?>

                    <?php if ($repliesNb > 0) { ?>
                        <a href="?see=<?php echo $single['id']; ?>">Voir les commentaires (<?php echo $repliesNb; ?>)</a><br><br>
                    <?php } ?>
                    <a href="https://veloinfo.ca/contribution/<?php echo $single['id']; ?> ">Voir sur l'app</a>

                </td>
            </tr>
        <?php
        }

        ?>
    </table>