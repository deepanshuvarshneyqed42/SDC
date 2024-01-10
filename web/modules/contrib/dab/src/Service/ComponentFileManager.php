<?php

namespace Drupal\dab\Service;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sdc\Plugin\Component;

/**
 * A class to create files for a new component.
 *
 * @package Drupal\dab\Service
 */
class ComponentFileManager {

  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private MessengerInterface $messenger;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private ModuleExtensionList $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  private ThemeExtensionList $themeExtensionList;

  /**
   * ComponentFileManager constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension list.
   */
  public function __construct(
    FileSystemInterface $file_system,
    MessengerInterface $messenger,
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list
  ) {
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * Get the dab module path.
   *
   * @return string
   *   The dab module path.
   */
  public function getDabModulePath(): string {
    return $this->moduleExtensionList->getPath('dab');
  }

  /**
   * Build the component folder path.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string|null $componentType
   *   The component type.
   *
   * @return string
   *   The component folder path.
   */
  public function buildComponentFolderPath(
    string $machineName,
    string $provider,
    ?string $componentType = NULL
  ): string {
    $themeOrModulePath = $provider;

    // Get the theme of module path.
    if ($this->themeExtensionList->exists($provider)) {
      $themeOrModulePath = $this->themeExtensionList->getPath($provider);
    }

    if ($this->moduleExtensionList->exists($provider)) {
      $themeOrModulePath = $this->moduleExtensionList->getPath($provider);
    }

    $componentTypePath = !empty($componentType) ? "$componentType/" : '';
    return "$themeOrModulePath/components/$componentTypePath$machineName";
  }

  /**
   * Create the component folder.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string|null $componentType
   *   The component type.
   *
   * @return bool
   *   TRUE if the folder was created.
   */
  public function createComponentFolder(
    string $machineName,
    string $provider,
    ?string $componentType = NULL
  ): bool {
    $path = $this->buildComponentFolderPath($machineName, $provider, $componentType);
    return $this->fileSystem->prepareDirectory(
      $path,
      FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );
  }

  /**
   * Create the component yaml file.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string $name
   *   The component name.
   * @param string|null $componentType
   *   The component type.
   * @param string|null $description
   *   The component description.
   *
   * @return bool
   *   TRUE if the file was created.
   */
  public function createComponentFile(
    string $machineName,
    string $provider,
    string $name,
    ?string $componentType,
    ?string $description = NULL
  ): bool {
    $file = fopen(
      $this->buildComponentFolderPath($machineName, $provider, $componentType) . "/$machineName.component.yml",
      'x'
    );

    if (!$file) {
      return FALSE;
    }

    $yamlArray = [
      '# Documentation' => 'https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components/annotated-example-componentyml',
      'name' => $name,
      'status' => 'experimental',
    ];

    if (!empty($componentType)) {
      $yamlArray['group'] = $componentType;
    }

    if (!empty($description)) {
      $yamlArray['description'] = $description;
    }

    $yamlArray['props'] = [
      'type' => 'object',
      'properties' => [
        'name' => [
          'type' => 'string',
          'examples' => [
            'Example 1' => 'Hello world',
          ],
        ],
      ],
    ];

    $yaml = Yaml::encode($yamlArray);
    fwrite($file, $yaml);
    return fclose($file);
  }

  /**
   * Create the component twig file.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string|null $componentType
   *   The component type.
   *
   * @return bool
   *   TRUE if the file was created.
   */
  public function createTwigFile(
    string $machineName,
    string $provider,
    ?string $componentType
  ): bool {
    $file = fopen(
      $this->buildComponentFolderPath($machineName, $provider, $componentType) . "/$machineName.twig",
      'x'
    );

    if (!$file) {
      return FALSE;
    }

    fwrite($file, "{# @file\n");
    fwrite($file, "  @component: $machineName\n");
    fwrite($file, "  @props:\n");
    fwrite($file, "    - name:\n");
    fwrite($file, "      type: string\n");
    fwrite($file, "#}\n");
    fwrite($file, "{{ name }}");
    return fclose($file);
  }

  /**
   * Create the component readme file.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string $name
   *   The component name.
   * @param string $description
   *   The component description.
   * @param string $componentType
   *   The component type.
   *
   * @return bool
   *   TRUE if the file was created.
   */
  public function createReadmeFile(
    string $machineName,
    string $provider,
    string $name,
    string $description,
    ?string $componentType
  ): bool {
    $file = fopen(
      $this->buildComponentFolderPath($machineName, $provider, $componentType) . "/README.md",
      'x'
    );

    if (!$file) {
      return FALSE;
    }

    fwrite($file, "# $name \n");
    fwrite($file, "\n");
    fwrite($file, "$description\n");
    fwrite($file, "# Usage \n");
    fwrite($file, "\n");
    fwrite($file, "Describe the usage of your componant here. \n");
    fwrite($file, "# Additional Info\n");
    fwrite($file, "\n");
    fwrite($file, "Add additional info if needed here. \n");
    return fclose($file);
  }

  /**
   * Create the component js file.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string|null $componentType
   *   The component type.
   *
   * @return bool
   *   TRUE if the file was created.
   */
  public function createJsFile(
    string $machineName,
    string $provider,
    ?string $componentType
  ): bool {
    $file = fopen(
      $this->buildComponentFolderPath($machineName, $provider, $componentType) . "/$machineName.js",
      'x'
    );

    if (!$file) {
      return FALSE;
    }

    fwrite($file, "(function (Drupal) {\n");
    fwrite($file, "  Drupal.behavior.$machineName = {\n");
    fwrite($file, "    attach: function attach(context) {\n");
    fwrite($file, "      console.log('$machineName JS');\n");
    fwrite($file, "    }\n");
    fwrite($file, "  };\n");
    fwrite($file, "})(Drupal);\n");
    return fclose($file);
  }

  /**
   * Create the component css file.
   *
   * @param string $machineName
   *   The component machine name.
   * @param string $provider
   *   The component provider.
   * @param string|null $componentType
   *   The component type.
   *
   * @return bool
   *   TRUE if the file was created.
   */
  public function createCssFile(
    string $machineName,
    string $provider,
    ?string $componentType
  ): bool {
    $file = fopen(
      $this->buildComponentFolderPath($machineName, $provider, $componentType) . "/$machineName.css",
      'x'
    );

    if (!$file) {
      return FALSE;
    }

    fwrite($file, "/*\n");
    fwrite($file, " * $machineName CSS\n");
    fwrite($file, " */\n");
    return fclose($file);
  }

  /**
   * Load the component yaml file.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   *
   * @return array
   *   The component yaml file.
   */
  public function loadComponentFile(Component $component): array {
    $pluginDefinition = $component->getPluginDefinition();
    $filePath = $pluginDefinition['_discovered_file_path'];

    if (!file_exists($filePath)) {
      return [];
    }

    // Load YAML file.
    $yaml = file_get_contents($filePath);
    // Decode YAML file.
    $yaml = Yaml::decode($yaml);
    return $yaml;
  }

  /**
   * Save the component yaml file.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param array $yamlArray
   *   The component yaml file.
   *
   * @return bool
   *   TRUE if the file was saved.
   */
  public function saveComponentFile(Component $component, array $yamlArray): bool {
    $pluginDefinition = $component->getPluginDefinition();
    $filePath = $pluginDefinition['_discovered_file_path'];
    $yaml = Yaml::encode($yamlArray);
    file_put_contents($filePath, $yaml);

    $this->messenger->addMessage($this->t('File @file has been saved successfully', ['@file' => $filePath]));
    return TRUE;
  }

  /**
   * Delete the component file.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param string $fileType
   *   The file type.
   *
   * @return bool
   *   TRUE if the file was deleted.
   */
  public function deleteComponentFile(Component $component, string $fileType): bool {
    $pluginDefinition = $component->getPluginDefinition();
    $path = $pluginDefinition['path'];
    $machineName = $pluginDefinition['machineName'];
    $filePath = "$path/$machineName.$fileType";

    if (file_exists($filePath)) {
      unlink($filePath);
      $this->messenger->addMessage($this->t('File @file has been removed successfully', ['@file' => $filePath]));
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Duplicate the component files and folder.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param string $newProvider
   *   The new component provider.
   *
   * @return bool
   *   TRUE if the folder was duplicated.
   */
  public function duplicateComponent(Component $component, string $newProvider): bool {
    $metadata = $component->metadata;
    $group = $metadata->group;
    $machineName = $metadata->machineName;
    $path = $metadata->path;
    $newPath = $this->buildComponentFolderPath($machineName, $newProvider, $group);

    if ($newPath === $path) {
      return FALSE;
    }

    if (file_exists($newPath)) {
      $this->messenger->addError($this->t('Folder @folder already exists', ['@folder' => $newPath]));
      return FALSE;
    }

    // Build the realpath of the new folder.
    $groupPath = dirname($newPath);
    $newPath = (realpath($groupPath) ?: $groupPath) . '/' . basename($newPath);

    $this->fileSystem->prepareDirectory($newPath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Copy the files from old folder to new folder.
    $dir = new \RecursiveDirectoryIterator($path);

    foreach ($dir as $file) {
      $newFilePath = str_replace($path, $newPath, $file);

      if (is_dir($file)) {
        $this->fileSystem->prepareDirectory($newFilePath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      }
      else {
        $this->fileSystem->copy($file, $newFilePath, FileSystemInterface::EXISTS_REPLACE);
      }

      $dir->next();
    }

    $this->messenger->addMessage($this->t(
      'Folder @folder has been duplicated successfully to @new_folder',
      [
        '@folder' => $path,
        '@new_folder' => $newPath,
      ]
    ));

    return TRUE;
  }

  /**
   * Move the component files and folder.
   *
   * @param \Drupal\sdc\Plugin\Component $component
   *   The component.
   * @param string $newMachineName
   *   The new component machine name.
   * @param string $newProvider
   *   The new component provider.
   * @param string|null $newComponentType
   *   The new component type.
   *
   * @return bool
   *   TRUE if the folder was moved.
   */
  public function moveComponentFolder(
    Component $component,
    string $newMachineName,
    string $newProvider,
    ?string $newComponentType = NULL
  ): bool {
    $metadata = $component->metadata;
    $machineName = $metadata->machineName;
    $path = $metadata->path;
    $newPath = $this->buildComponentFolderPath($newMachineName, $newProvider, $newComponentType);

    if ($newPath === $path) {
      return FALSE;
    }

    if (file_exists($newPath)) {
      $this->messenger->addError($this->t('Folder @folder already exists', ['@folder' => $newPath]));
      return FALSE;
    }

    $extensions = ['twig', 'js', 'css', 'component.yml', 'README.md'];

    foreach ($extensions as $extension) {
      if (!file_exists("$path/{$machineName}.$extension") || $machineName === $newMachineName) {
        continue;
      }
      $this->fileSystem->move("$path/{$machineName}.$extension", "$path/$newMachineName.$extension");
    }

    $newPathDirectory = dirname($newPath);
    $this->fileSystem->prepareDirectory($newPathDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->fileSystem->move($path, $newPath);

    $this->messenger->addMessage($this->t(
      'Folder @folder has been moved successfully to @new_folder',
      [
        '@folder' => $path,
        '@new_folder' => $newPath,
      ]
    ));

    return TRUE;
  }

  /**
   * Get the provider's libraries files.
   *
   * @param string $provider
   *   The component provider.
   *
   * @return array
   *   The component libraries files.
   */
  public function getLibrariesFilesFromExtension(string $provider): array {
    $themeOrModulePath = $provider;

    // Get the theme of module path.
    if ($this->themeExtensionList->exists($provider)) {
      $themeOrModulePath = $this->themeExtensionList->getPath($provider);
    }

    if ($this->moduleExtensionList->exists($provider)) {
      $themeOrModulePath = $this->moduleExtensionList->getPath($provider);
    }

    $libraryFile = "$themeOrModulePath/$provider.libraries.yml";

    if (!file_exists($libraryFile)) {
      return [];
    }

    $library = Yaml::decode(file_get_contents($libraryFile));
    $flattenedLibraries = ['js' => [], 'css' => []];

    $flatenLibraryFiles = function ($library) use (&$flatenLibraryFiles, &$flattenedLibraries, $themeOrModulePath) {
      foreach ($library as $libraryKey => $libraryValues) {
        if (!is_array($libraryValues)) {
          continue;
        }

        if (count($libraryValues) > 0) {
          $flatenLibraryFiles($libraryValues);
        }
        else {
          $extension = pathinfo($libraryKey, PATHINFO_EXTENSION);
          $flattenedLibraries[$extension][] = "$themeOrModulePath/$libraryKey";
        }
      }
    };

    $flatenLibraryFiles($library);
    return $flattenedLibraries;
  }

}
