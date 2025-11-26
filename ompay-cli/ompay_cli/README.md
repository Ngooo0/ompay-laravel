# OMPAY CLI

Client en ligne de commande pour le système de paiement OMPAY.

## Installation

```bash
# Cloner le repository
git clone <repository-url>
cd ompay-cli

# Installer les dépendances
dart pub get

# Rendre exécutable
chmod +x bin/ompay_cli.dart
```

## Utilisation

### Authentification

```bash
# Connexion (envoie un OTP)
dart run bin/ompay_cli.dart auth login --phone +221771234567 --password monmotdepasse

# Vérification OTP
dart run bin/ompay_cli.dart auth verify-otp --phone +221771234567 --otp 123456

# Déconnexion
dart run bin/ompay_cli.dart auth logout
```

### Transactions

```bash
# Dépôt d'argent
dart run bin/ompay_cli.dart transaction deposit --amount 50000

# Retrait d'argent
dart run bin/ompay_cli.dart transaction withdraw --amount 20000

# Transfert
dart run bin/ompay_cli.dart transaction transfer --to +221778765432 --amount 15000

# Paiement marchand
dart run bin/ompay_cli.dart transaction pay --merchant M123 --amount 25000

# Liste des transactions
dart run bin/ompay_cli.dart transaction list --limit 20
```

### Informations compte

```bash
# Afficher solde et informations
dart run bin/ompay_cli.dart balance show
```

## Configuration

Le CLI utilise l'API OMPAY déployée sur Render. Les tokens d'authentification sont sauvegardés localement dans `.ompay_token`.

## Développement

```bash
# Tests
dart test

# Analyse statique
dart analyze

# Formatage
dart format .
```

## API Endpoints utilisés

- `POST /api/auth/login` - Connexion
- `POST /api/auth/verify-otp` - Vérification OTP
- `POST /api/transactions/deposit` - Dépôt
- `POST /api/transactions/withdraw` - Retrait
- `POST /api/transactions/transfer` - Transfert
- `POST /api/transactions/pay` - Paiement marchand
- `GET /api/comptes` - Informations compte
- `GET /api/comptes/transactions` - Liste transactions

## Licence

MIT
