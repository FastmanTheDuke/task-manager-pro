# 🪟 Guide Windows - Installation pdo_mysql

## 🚨 Problème identifié
L'extension **pdo_mysql** n'est pas activée dans votre installation PHP. C'est LA cause de l'erreur `PDO::MYSQL_ATTR_INIT_COMMAND`.

## 🔍 Identifier votre environnement PHP

D'abord, identifiez quel environnement PHP vous utilisez :

```cmd
php --version
php --ini
```

Les résultats vous indiqueront le chemin vers votre `php.ini`.

## 🛠️ Solutions par environnement Windows

### 📦 **Option 1 : XAMPP (le plus courant)**

Si vous utilisez **XAMPP** :

1. **Localiser php.ini :**
   ```
   C:\xampp\php\php.ini
   ```

2. **Ouvrir php.ini** avec un éditeur de texte (Notepad++, VS Code, etc.)

3. **Rechercher la ligne :** (Ctrl+F)
   ```ini
   ;extension=pdo_mysql
   ```

4. **Supprimer le `;` :**
   ```ini
   extension=pdo_mysql
   ```

5. **Sauvegarder le fichier**

6. **Redémarrer Apache** via le panneau de contrôle XAMPP

7. **Vérifier :**
   ```cmd
   php -m | findstr pdo_mysql
   ```

### 📦 **Option 2 : WampServer**

Si vous utilisez **WampServer** :

1. **Clic droit** sur l'icône WampServer (système tray)
2. **PHP** → **PHP extensions**
3. **Cocher** `pdo_mysql`
4. **Redémarrer tous les services**

### 📦 **Option 3 : MAMP (Windows)**

Si vous utilisez **MAMP** :

1. **Ouvrir MAMP**
2. **Preferences** → **PHP**
3. **Vérifier** que `pdo_mysql` est activé
4. **Redémarrer** les services

### 📦 **Option 4 : PHP Standalone**

Si vous avez installé PHP manuellement :

1. **Localiser php.ini :** (utiliser `php --ini`)
2. **Même procédure que XAMPP** (étapes 2-7 ci-dessus)
3. **Redémarrer** votre serveur web

## ✅ Vérification de l'installation

Après avoir suivi les étapes :

### 1. **Test en ligne de commande :**
```cmd
php -m | findstr pdo
```

**Résultat attendu :**
```
pdo_mysql
pdo_sqlite
PDO
```

### 2. **Test avec notre script :**
```cmd
php test_fix.php
```

**Résultat attendu :**
```
1️⃣ Vérification des extensions PHP...
   - pdo: ✅ ACTIVÉE
   - pdo_mysql: ✅ ACTIVÉE  ← MAINTENANT OK!
   - json: ✅ ACTIVÉE
   - mbstring: ✅ ACTIVÉE
```

### 3. **Test de l'application :**
```cmd
cd backend
php -S localhost:8000
```

Dans un autre terminal :
```cmd
curl http://localhost:8000/api/health
```

## 🚨 Dépannage

### **Problème : php.ini introuvable**
```cmd
php --ini
```
Cela vous donnera le chemin exact.

### **Problème : Plusieurs php.ini**
Utilisez celui indiqué par `Loaded Configuration File:` dans la sortie de `php --ini`.

### **Problème : Changements ignorés**
1. Vérifiez que vous éditez le bon `php.ini`
2. Redémarrez complètement Apache/serveur web
3. Vérifiez qu'il n'y a pas d'espaces avant `extension=pdo_mysql`

### **Problème : Extension non trouvée**
Si l'extension n'existe pas dans votre php.ini, ajoutez-la :
```ini
extension=pdo_mysql
```

## 🎯 Solution alternative rapide

Si vous ne trouvez pas votre php.ini ou si c'est trop compliqué, **réinstallez XAMPP** :

1. **Télécharger** la dernière version de XAMPP depuis [apachefriends.org](https://www.apachefriends.org/)
2. **Désinstaller** l'ancienne version
3. **Installer** la nouvelle version
4. **pdo_mysql est activé par défaut** dans les versions récentes

## 📋 Checklist finale

- [ ] Extension pdo_mysql activée dans php.ini
- [ ] Serveur web redémarré
- [ ] `php -m | findstr pdo_mysql` retourne `pdo_mysql`
- [ ] `php test_fix.php` affiche pdo_mysql en vert
- [ ] API répond sur `http://localhost:8000/api/health`
- [ ] Login fonctionne sans erreur 500

## 🆘 Si ça ne marche toujours pas

**Envoyez-moi la sortie de :**
```cmd
php --version
php --ini
php -m
```

Et je pourrai vous aider plus spécifiquement !

---

💡 **Note :** L'erreur `PDO::MYSQL_ATTR_INIT_COMMAND` disparaîtra automatiquement une fois pdo_mysql installé, grâce aux corrections que j'ai apportées au code.
