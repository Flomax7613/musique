<?php
// Connexion à la base de données
$host = 'localhost';
$db = 'Musique';
$user = 'root';
$pass = 'root';

try {
    // Création de la connexion PDO avec les paramètres fournis
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Gestion des erreurs
} catch (PDOException $e) {
    // Si la connexion échoue, afficher un message d'erreur
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des données depuis la base de données
$sql = "SELECT Artiste, Nom, Durée, Lien, Instrument FROM Musiques AS M INNER JOIN Instruments AS I ON M.IdInstrument = I.Id";
$stmt = $pdo->prepare($sql); // Préparation de la requête SQL
$stmt->execute(); // Exécution de la requête
$musiques = $stmt->fetchAll(PDO::FETCH_ASSOC); // Récupération des résultats sous forme de tableau associatif

// Extraction des instruments uniques à partir des musiques récupérées
$instruments = array_unique(array_column($musiques, 'Instrument'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Musique</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Projet Musique</h1>
    </header>
    <main>
        <!-- Barre de recherche -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Rechercher une musique ou un instrument...">
        </div>

        <!-- Bouton d'ajout de musique -->
<div class="add-music-btn">
    <button onclick="toggleAddMusicForm()">Ajouter une musique</button>
</div>

<!-- Menu déroulant et formulaire d'ajout de musique -->
<div id="addMusicForm" style="display: none; margin-top: 20px;">
    <form method="POST" action="">
        <div>
            <label for="artiste">Artiste :</label>
            <input type="text" id="artiste" name="artiste" required><br><br>
        </div>

        <div>
            <label for="nom">Titre de la musique :</label>
            <input type="text" id="nom" name="nom" required><br><br>
        </div>

        <div>
            <label for="duree">Durée :</label>
            <input type="text" id="duree" name="duree" placeholder="00:00" pattern="([0-5]?[0-9]):([0-5]?[0-9])" required><br><br>
            <?php
            // Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des valeurs envoyées par le formulaire
    $artiste = $_POST['artiste'];
    $nom = $_POST['nom'];
    $duree = $_POST['duree'];
    $lien = $_POST['lien'];
    $instrument = $_POST['instrument'];

    // Validation de la durée (format mm:ss)
    if (!preg_match("/^([0-5]?[0-9]):([0-5]?[0-9])$/", $duree)) {
        echo "<script>alert('Veuillez entrer la durée au format mm:ss');</script>";
    } else {
        // Insertion de la musique dans la base de données
        $sql = "INSERT INTO Musiques (Artiste, Nom, Durée, Lien, IdInstrument) VALUES (?, ?, ?, ?, (SELECT Id FROM Instruments WHERE Instrument = ?))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$artiste, $nom, $duree, $lien, $instrument]);

        // Redirection ou message de confirmation
        echo "<script>alert('Musique ajoutée avec succès');</script>";
    }
}

            ?>
        </div>

        <div>
            <label for="lien">Lien :</label>
            <input type="text" id="lien" name="lien" required><br><br>
        </div>

        <div>
            <label for="instrument">Instrument :</label>
            <select id="instrument" name="instrument" required>
                <!-- Liste des instruments récupérés depuis PHP -->
                <?php foreach ($instruments as $instrument): ?>
                    <option value="<?php echo htmlspecialchars($instrument); ?>"><?php echo htmlspecialchars($instrument); ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>

        <button type="submit">Ajouter</button>
    </form>
</div>

<script>
    // Fonction pour afficher ou masquer le menu déroulant du formulaire d'ajout
    function toggleAddMusicForm() {
        var form = document.getElementById('addMusicForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>

        
        <!-- Section de filtre par instrument -->
        <div class="filter-section">
            <button onclick="toggleDropdown()">Sélectionner les instruments</button>
            <div id="dropdown" class="dropdown-content" style="display: none;">
                <label><input type="checkbox" id="allInstruments" checked> Tout</label>
                <!-- Liste des instruments disponibles -->
                <?php foreach ($instruments as $instrument): ?>
                    <label><input type="checkbox" class="instrumentCheckbox" value="<?php echo htmlspecialchars($instrument); ?>"> <?php echo htmlspecialchars($instrument); ?></label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Conteneur des tables -->
        <div id="tables-container">
            <table id="musicTable">
                <thead>
                    <tr>
                        <th class="sortable">Artiste</th>
                        <th class="sortable">Titre</th>
                        <th class="sortable">Durée</th>
                        <th>Lien</th>
                        <th class="sortable">Instrument</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <!-- Boucle pour afficher chaque musique dans une ligne de tableau -->
                    <?php foreach ($musiques as $musique): ?>
                    <tr class="music-row" data-instrument="<?php echo htmlspecialchars($musique['Instrument']); ?>">
                        <td><?php echo htmlspecialchars($musique['Artiste']); ?></td>
                        <td><?php echo htmlspecialchars($musique['Nom']); ?></td>
                        <td><?php echo htmlspecialchars($musique['Durée']); ?></td>
                        <td><?php echo htmlspecialchars($musique['Lien']); ?></td>
                        <td><?php echo htmlspecialchars($musique['Instrument']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Filtrer les résultats en fonction de la recherche
        document.getElementById('searchInput').addEventListener('keyup', function() {
            var input = document.getElementById('searchInput').value.toLowerCase();
            var rows = document.querySelectorAll('#musicTable tbody tr');

            rows.forEach(function(row) {
                var artiste = row.children[0].textContent.toLowerCase();
                var nom = row.children[1].textContent.toLowerCase();
                var durée = row.children[2].textContent.toLowerCase();
                var lien = row.children[3].textContent.toLowerCase();
                var instrument = row.children[4].textContent.toLowerCase();

                // Afficher ou masquer les lignes en fonction de la correspondance avec le texte de recherche
                if (artiste.includes(input) || nom.includes(input) || durée.includes(input) || lien.includes(input) || instrument.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Fonction pour trier les colonnes du tableau
        document.querySelectorAll('th.sortable').forEach(function(header) {
            header.addEventListener('click', function() {
                var table = this.closest('table');
                var isAscending = this.classList.contains('asc');
                var columnIndex = Array.from(this.parentNode.children).indexOf(this);
                sortTable(table, columnIndex, isAscending);
                this.classList.toggle('asc'); // Inverser l'ordre du tri à chaque clic
            });
        });

        // Fonction de tri du tableau en fonction de la colonne cliquée
        function sortTable(table, columnIndex, isAscending) {
            var rows = Array.from(table.querySelectorAll('tbody tr'));

            rows.sort(function(rowA, rowB) {
                var cellA = rowA.children[columnIndex].textContent.toLowerCase();
                var cellB = rowB.children[columnIndex].textContent.toLowerCase();

                if (cellA < cellB) return isAscending ? 1 : -1;
                if (cellA > cellB) return isAscending ? -1 : 1;
                return 0;
            });

            // Réorganiser les lignes du tableau après le tri
            rows.forEach(function(row) {
                table.querySelector('tbody').appendChild(row);
            });
        }

        // Afficher ou masquer le menu déroulant des instruments
        function toggleDropdown() {
            var dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Cocher/décocher tous les instruments en même temps
        document.getElementById('allInstruments').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.instrumentCheckbox');
            var isChecked = this.checked;

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });

            displayRows(isChecked, []); // Afficher ou non toutes les lignes
        });

        // Gérer les instruments sélectionnés pour filtrer les musiques
        document.querySelectorAll('.instrumentCheckbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var selectedInstruments = Array.from(document.querySelectorAll('.instrumentCheckbox:checked')).map(function(checkbox) {
                    return checkbox.value;
                });

                document.getElementById('allInstruments').checked = false;
                displayRows(false, selectedInstruments); // Afficher les musiques selon les instruments sélectionnés
            });
        });

        // Fonction pour afficher les lignes en fonction de l'instrument sélectionné
        function displayRows(showAll, instruments) {
            var container = document.getElementById('tables-container');
            container.innerHTML = '';

            if (showAll) {
                var allTable = createTable('Toutes les musiques', <?php echo json_encode($musiques); ?>);
                container.appendChild(allTable);
            } else {
                instruments.forEach(function(instrument) {
                    var filteredMusiques = <?php echo json_encode($musiques); ?>.filter(function(musique) {
                        return musique.Instrument === instrument;
                    });

                    if (filteredMusiques.length > 0) {
                        var instrumentTable = createTable(instrument, filteredMusiques);
                        addSortingToTable(instrumentTable); // Ajouter la fonctionnalité de tri à la nouvelle table
                        container.appendChild(instrumentTable);
                    }
                });
            }
        }

        // Fonction pour créer une table dynamique avec les musiques filtrées
        function createTable(title, musiques) {
            var table = document.createElement('table');
            var thead = document.createElement('thead');
            var tbody = document.createElement('tbody');

            var headerRow = document.createElement('tr');
            ['Artiste', 'Titre', 'Durée', 'Lien', 'Instrument'].forEach(function(header) {
                var th = document.createElement('th');
                th.textContent = header;
                th.classList.add('sortable');
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);

            musiques.forEach(function(musique) {
                var row = document.createElement('tr');
                ['Artiste', 'Nom', 'Durée', 'Lien', 'Instrument'].forEach(function(key) {
                    var td = document.createElement('td');
                    td.textContent = musique[key];
                    row.appendChild(td);
                });
                tbody.appendChild(row);
            });

            var titleElement = document.createElement('h2');
            titleElement.textContent = title;
            table.appendChild(thead);
            table.appendChild(tbody);

            var tableContainer = document.createElement('div');
            tableContainer.appendChild(titleElement);
            tableContainer.appendChild(table);
            
            return tableContainer;
        }

        // Ajouter la fonctionnalité de tri à la nouvelle table générée dynamiquement
        function addSortingToTable(table) {
            table.querySelectorAll('th.sortable').forEach(function(header) {
                header.addEventListener('click', function() {
                    var isAscending = this.classList.contains('asc');
                    var columnIndex = Array.from(this.parentNode.children).indexOf(this);
                    sortTable(table, columnIndex, isAscending);
                    this.classList.toggle('asc');
                });
            });
        }
    </script>
</body>
</html>
