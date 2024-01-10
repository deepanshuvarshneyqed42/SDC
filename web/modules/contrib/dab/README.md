# Drupal Atomic Builder

Drupal Atomic Builder is a module that allows you to create modules directly from the Drupal administration following the [Atomic Design System pattern](https://atomicdesign.bradfrost.com/chapter-2/), giving the opportunity for front end developpers to have a specific page to code their components and to test them.

## Dependencies

This modules uses the following libraries:

- [league/commonmark](https://commonmark.thephpleague.com/2.4/installation/): for Markdown support
- [Single Directory Component](https://www.drupal.org/project/sdc)

## Installation

```
composer require drupal/dab
drush en dab -y
```

## Usage

You can access the components list page here : `admin/dab/components`

You can set your component types here : `/admin/dab/components/settings`

Once you are on a component you can :
- View it
- Edit it
- Duplicate it
- Delete it

You also have some permissions to set :
- `administer dab configuration`
- `administer dab components`
- `access dab components`

