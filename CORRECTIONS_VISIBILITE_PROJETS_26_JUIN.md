# Corrections de Visibilité des Projets - 26 Juin 2025

## Problème Identifié

Le système permettait à **tous les utilisateurs** de voir les projets par défaut, même s'ils n'étaient pas membres. Cette comportement était causé par la logique SQL suivante :

```sql
WHERE (pm.user_id = :user_id OR p.is_public = 1)
```

## Solution Appliquée

### 1. Modification du Modèle Project

**Changements principaux :**

- **Création de projet** : `is_public` est maintenant **toujours 0** par défaut
- **Visibilité** : Seuls les **membres du projet** peuvent le voir
- **Jointures SQL** : Utilisation de `INNER JOIN` au lieu de `LEFT JOIN` avec `project_members`

### 2. Méthodes Corrigées

#### `createProject()`
```php
'is_public' => 0, // Toujours 0 par défaut, seuls les membres peuvent voir
```

#### `getProjectsForUser()`
```sql
-- AVANT
WHERE (pm.user_id = :user_id OR p.is_public = 1)

-- APRÈS  
WHERE pm.user_id = :user_id  -- Seuls les membres voient les projets
```

#### `getProjectById()`
```sql
-- Utilise maintenant INNER JOIN pour s'assurer que seuls les membres voient les projets
INNER JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = :user_id
```

### 3. Nouvelles Fonctionnalités Ajoutées

#### Gestion des Membres de Projets

**Nouvelles méthodes dans Project.php :**
- `addMemberToProject()` - Ajouter un membre avec validation des permissions
- `removeMemberFromProject()` - Retirer un membre avec validation des permissions

**Nouvelles routes API :**
- `GET /api/projects/{id}/members` - Lister les membres
- `POST /api/projects/{id}/members` - Ajouter un membre
- `DELETE /api/projects/{id}/members/{userId}` - Retirer un membre

## Fonctionnement Actuel

### Création d'un Projet
1. Le propriétaire crée un projet
2. Il devient automatiquement membre avec le rôle "owner"
3. Le projet n'est visible que par lui

### Ajout de Membres
1. Seuls les propriétaires et admins peuvent ajouter des membres
2. Les rôles disponibles : `viewer`, `member`, `admin`, `owner`
3. Une fois ajouté, le membre peut voir le projet

### Visibilité
- ✅ **Visible** : Projets où l'utilisateur est membre
- ❌ **Non visible** : Tous les autres projets (même s'ils sont techniquement "publics")

## Réponses à vos Questions

### Différence entre Projets et Tâches

**Projets :**
- Conteneurs organisationnels
- Ont des membres avec des rôles
- Visibilité contrôlée par les membres
- Peuvent contenir plusieurs tâches

**Tâches :**
- Unités de travail concrètes
- Peuvent être liées à un projet (`project_id`) ou indépendantes
- Ont un créateur et peuvent être assignées
- Visibilité basée sur le créateur/assigné

### Architecture Recommandée

```
Utilisateur
    ↓
Projets (membre)
    ↓
Tâches (dans le projet ou indépendantes)
```

## Test des Corrections

Pour tester le nouveau comportement :

1. **Créer un projet** avec l'utilisateur A
2. **Se connecter** avec l'utilisateur B
3. **Vérifier** que B ne voit pas le projet de A
4. **Ajouter B comme membre** du projet (via A)
5. **Vérifier** que B peut maintenant voir le projet

## API Exemples

### Ajouter un membre
```bash
POST /api/projects/1/members
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "user_id": 2,
  "role": "member"
}
```

### Lister les membres
```bash
GET /api/projects/1/members
Authorization: Bearer YOUR_TOKEN
```

### Retirer un membre
```bash
DELETE /api/projects/1/members/2
Authorization: Bearer YOUR_TOKEN
```

Les corrections garantissent maintenant que **seuls les utilisateurs désignés comme membres** peuvent voir et accéder aux projets.
