<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Définition du titre de la page
$pageTitle = 'Guide des tailles';
// Définition des feuilles de style supplémentaires
$pageStyles = [BASE_URL . '/assets/css/pages.css'];
// Inclusion de l'en-tête HTML et de la barre de navigation
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<!-- Bannière principale du guide des tailles -->
<div class="page-banner"><div class="container"><h1>Guide des tailles</h1><p>Trouvez la taille parfaite pour chaque membre de la famille.</p></div></div>
<!-- Conteneur du contenu de la page -->
<div class="container page-content">
    <!-- Section : Tableau des tailles femme -->
    <div class="page-section">
        <h2>Femme (en cm)</h2>
        <div class="size-guide-table-wrapper"><table class="size-guide-table">
            <!-- En-tête du tableau femme -->
            <tr><th>Taille</th><th>Poitrine</th><th>Taille</th><th>Hanches</th></tr>
            <!-- Lignes de mesures pour chaque taille femme -->
            <tr><td>XS</td><td>80-84</td><td>60-64</td><td>88-92</td></tr>
            <tr><td>S</td><td>84-88</td><td>64-68</td><td>92-96</td></tr>
            <tr><td>M</td><td>88-92</td><td>68-72</td><td>96-100</td></tr>
            <tr><td>L</td><td>92-96</td><td>72-76</td><td>100-104</td></tr>
            <tr><td>XL</td><td>96-100</td><td>76-80</td><td>104-108</td></tr>
            <tr><td>XXL</td><td>100-104</td><td>80-84</td><td>108-112</td></tr>
        </table></div>
    </div>
    <!-- Section : Tableau des tailles homme -->
    <div class="page-section">
        <h2>Homme (en cm)</h2>
        <div class="size-guide-table-wrapper"><table class="size-guide-table">
            <!-- En-tête du tableau homme -->
            <tr><th>Taille</th><th>Poitrine</th><th>Taille</th><th>Longueur bras</th></tr>
            <!-- Lignes de mesures pour chaque taille homme -->
            <tr><td>S</td><td>88-92</td><td>76-80</td><td>60-62</td></tr>
            <tr><td>M</td><td>92-96</td><td>80-84</td><td>62-64</td></tr>
            <tr><td>L</td><td>96-100</td><td>84-88</td><td>64-66</td></tr>
            <tr><td>XL</td><td>100-104</td><td>88-92</td><td>66-68</td></tr>
            <tr><td>XXL</td><td>104-108</td><td>92-96</td><td>68-70</td></tr>
        </table></div>
    </div>
    <!-- Section : Tableau des tailles enfant -->
    <div class="page-section">
        <h2>Enfant (âge / taille en cm)</h2>
        <div class="size-guide-table-wrapper"><table class="size-guide-table">
            <!-- En-tête du tableau enfant -->
            <tr><th>Âge</th><th>Taille (cm)</th><th>Poitrine</th><th>Hanches</th></tr>
            <!-- Lignes de mesures pour chaque âge enfant -->
            <tr><td>2 ans</td><td>86-92</td><td>50-52</td><td>50-54</td></tr>
            <tr><td>4 ans</td><td>98-104</td><td>52-54</td><td>54-58</td></tr>
            <tr><td>6 ans</td><td>110-116</td><td>54-56</td><td>58-62</td></tr>
            <tr><td>8 ans</td><td>122-128</td><td>58-62</td><td>62-66</td></tr>
            <tr><td>10 ans</td><td>134-140</td><td>64-68</td><td>66-72</td></tr>
            <tr><td>12 ans</td><td>146-152</td><td>70-76</td><td>74-80</td></tr>
            <tr><td>14 ans</td><td>158-164</td><td>78-84</td><td>82-88</td></tr>
            <tr><td>16 ans</td><td>164-170</td><td>84-90</td><td>86-92</td></tr>
        </table></div>
    </div>
    <!-- Conseil pour le choix des tailles -->
    <p class="text-muted text-sm" style="margin-top:16px;">Mesurez-vous directement sur le corps, sans serrer. Si vous hésitez entre deux tailles, choisissez la plus grande.</p>
</div>
<?php
// Inclusion du pied de page
require_once __DIR__ . '/../includes/footer.php'; ?>
