# 📚 Référence complète des fonctions et méthodes - Module media_album_light_table_style

**Date**: 9 avril 2026  
**Module**: media_album_light_table_style  
**Lieu**: `/web/modules/custom/media_album_light_table_style`  

> Ce document rassemble TOUTES les fonctions et méthodes publiques du module, avec leurs paramètres d'entrée, types de retour et descriptions.

---

## 📋 Table des matières

1. [Plugins Views - Style Gallery](#plugins-views--style-gallery)
2. [Traits](#traits)

---

## Plugins Views - Style Gallery

### MediaAlbumLightTableStyle
📄 Fichier: `src/Plugin/views/style/MediaAlbumLightTableStyle.php`

**Description**: Plugin de style Views pour galerie média en table "légère" avec grid responsive et métadonnées.

**Propriétés de classe:**
- `protected $usesRowPlugin = TRUE` - Utilise le plugin Drupal row
- `protected $usesFields = TRUE` - Utilise les champs Views
- `protected $usesGrouping = TRUE` - Support du groupage hierarchique

| # | Méthode | Paramètres | Retour | Description |
|---|---------|-----------|--------|-------------|
| 1 | `__construct()` | `$configuration` (array), `$plugin_id` (string), `$plugin_definition` (mixed), `$file_url_generator` (FileUrlGeneratorInterface), `$entity_type_manager` (EntityTypeManagerInterface), `$stream_wrapper_manager` (StreamWrapperManagerInterface), `$grouping_config_service` (AlbumGroupingConfigService) | **void** | Constructeur - Initialise plugin style avec 4 services injectés pour gérer URLs, entités, flux et groupement |
| 2 | `create()` | `$container` (ContainerInterface), `$configuration` (array), `$plugin_id` (string), `$plugin_definition` (array) | **MediaAlbumLightTableStyle** | Factory method statique - Crée instance du plugin via conteneur DI |
| 3 | `defineOptions()` | *(aucun)* | **array** | Définit options par défaut du style : image_thumbnail_style, columns, gap, justify, align, responsive, field_groups, show_ungrouped |
| 4 | `buildOptionsForm()` | `&$form` (array), `$form_state` (FormStateInterface) | **void** | Crée formulaire configuration du style Views avec zones : média (thumbnail, VBO, name, description, author), layout (colonnes, gap, alignment) |

---

## Traits

### MediaTrait
📄 Fichier: `src/Traits/MediaTrait.php`

**Description**: Trait fournissant la logique commune pour gestion des métadonnées média et thumbnails.

| # | Méthode | Paramètres | Retour | Description |
|---|---------|-----------|--------|-------------|
| 1 | `getMediaThumbnail()` | `$media` (EntityInterface), `$style_name` (string = 'medium') | **array** | Récupère métadonnées vignette du média : URL, alt, title, dimensions |
| 2 | `getThumbnailSize()` | `$style_name` (string = 'medium') | **array** | Retourne width et height du style image configuré |
| 3 | `getMediaEntity()` | `$row` (ResultRow) | **MediaInterface\|null** | Récupère entité média depuis ligne résultat View |
| 4 | `getMediaReferenceField()` | `$entity` (EntityInterface) | **string\|null** | Trouve champ référençant médias dans entité |
| 5 | `getFileUrl()` | `$uri` (string) | **string** | Génère URL publique fichier depuis URI |

---

## 📊 Résumé statistique

| Catégorie | Total |
|-----------|-------|
| **Plugins Views Style** | 1 classe × 4 méthodes |
| **Traits** | 1 × 5 méthodes |
| **Total fichiers PHP** | 2 |
| **Total fonctions/méthodes publiques** | **9** |

---

## 🎯 Architecture du module

Ce module est très **compact** et fournit un plugin Views Style spécialisé pour :

1. **Table légère responsive** (MediaAlbumLightTableStyle) - 4 méthodes pour configuration et rendu
2. **Gestion médias** (MediaTrait) - 5 méthodes pour thumbnails et références entités

Les fonctionnalités principales :
- Configuration flexible (colonnes, gap, alignment, thumbnail style)
- Support du groupage hierarchique des médias
- Intégration avec les champs Views standard
- Métadonnées médias extraites dynamiquement
- Images dérivées avec styles configurables

---

## 🔧 Dépendances

**Services injectés:**
- `file_url_generator` - Génération des URLs fichiersexécution
- `entity_type_manager` - Gestion des entités Drupal
- `stream_wrapper_manager` - Gestion des flux URI (public/private)
- `grouping_config_service` - Configuration du groupement d'albums (via media_album_av_common)

---

## 🔗 Intégration avec autres modules

Ce module s'intègre avec :
- **media_album_av_common** - Service AlbumGroupingConfigService
- **Views framework (Drupal core)** - Plugin style Views
- **Media entity system** - Entités et champs média

---

## 🔧 Comment utiliser ce document

1. **Configurer l'apparence**: Consultez MediaAlbumLightTableStyle::buildOptionsForm()
2. **Gérer thumbnails**: Consultez MediaTrait::getMediaThumbnail()
3. **Récupérer métadonnées**: Consultez MediaTrait (getMediaEntity, getFileUrl)
4. **Personnaliser layout**: Modifiez defineOptions() et buildOptionsForm()

---

**Dernière mise à jour**: 9 avril 2026
