# Contao Frontendsuche mit Loupe

- ungenaue Suche
- standardmäßig per AJAX
- aber auch eigene Lösung per API möglich
- Tags und Kategorien können je Seite optional angegeben werden
- Top-Treffer (eigenes BE-Modul) - optional
  
## Demo

![Demo](docs/search4you.gif)

## Installation

```bash
composer require c4y/search4you
```
## API

Wer sein eigenes Frontend bauen möchte, kann die Suchanfrage an die API schicken:

```http
GET http://localhost/search4you/search?query=suchbegriff&rootPage=1&featuredCategory=0
```

## to do

Die Erweiterung ist schon produktiv im Einsatz. Aber es fehlen noch ein paar Dinge:

- Systemwartung zur Bereinigung
- PDF Suche
- Optimierung des JS