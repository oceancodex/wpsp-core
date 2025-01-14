<?php

namespace WPSPCORE\Integration;

class YoastSEO {

	public mixed $robots                            = null;
	public mixed $canonical                         = null;
	public mixed $title                             = null;
	public mixed $description                       = null;

	public mixed $opengraphURL                      = null;
	public mixed $opengraphTitle                    = null;
	public mixed $opengraphDescription              = null;

	public mixed $schema                            = null;
	public mixed $schemaPriority                    = 10;
	public mixed $schemaAcceptedArgs                = 0;

	public mixed $schemaWebpage                     = null;
	public mixed $schemaWebpagePriority             = 10;
	public mixed $schemaWebpageAcceptedArgs         = 0;

	public mixed $schemaBreadcrumb                  = null;
	public mixed $schemaBreadcrumbPriority          = 10;
	public mixed $schemaBreadcrumbAcceptedArgs      = 0;

	public mixed $breadcrumbLinks                   = [];
	public mixed $breadcrumbLinksPriority           = 10;
	public mixed $breadcrumbLinksAcceptedArgs       = 0;

	public mixed $breadcrumbSingleLink              = [];
	public mixed $breadcrumbSingleLinkPriority      = 10;
	public mixed $breadcrumbSingleLinkAcceptedArgs  = 0;

	/*
	 *
	 */

	public function apply(): void {
		$this->robots();
		$this->canonical();
		$this->ogURL();
		$this->title();
		$this->description();
		$this->ogTitle();
		$this->ogDescription();
		$this->schema();
		$this->schemaWebpage();
		$this->schemaBreadcrumb();
		$this->breadcrumbLinks();
		$this->breadcrumbSingleLink();
	}

	/*
	 * Runner.
	 */

	public function robots(): void {
		if ($this->robots) {
			add_filter('wpseo_robots', function($robots, $presentation) {
				return $this->robots;
			}, 10, 2);
		}
	}

	public function canonical(): void {
		if ($this->canonical) {
			add_filter('wpseo_canonical', function($canonical) {
				return $this->canonical;
			});
		}
	}

	public function title(): void {
		if ($this->title) {
			add_filter('wpseo_title', function($title) {
				return $this->title;
			});
//			add_filter('pre_get_document_title', function($title) {
//				return $this->title;
//			}, 10000, 1);
		}
	}

	public function description(): void {
		if ($this->description) {
			add_filter('wpseo_metadesc', function($description) {
				return $this->description;
			});
		}
	}

	public function ogTitle(): void {
		if ($this->opengraphTitle) {
			add_filter('wpseo_opengraph_title', function($title) {
				return $this->opengraphTitle;
			});
		}
	}

	public function ogDescription(): void {
		if ($this->opengraphDescription) {
			add_filter('wpseo_opengraph_description', function($description) {
				return $this->opengraphDescription;
			});
		}
	}

	public function ogURL(): void {
		if ($this->opengraphURL) {
			add_filter('wpseo_opengraph_url', function($url) {
				return $this->opengraphURL;
			});
		}
	}

	public function schema(): void {
		if (is_callable($this->schema)) {
			add_filter('wpseo_schema_graph', $this->schema, $this->schemaPriority, $this->schemaAcceptedArgs);
		}
	}

	public function schemaBreadcrumb(): void {
		if (is_callable($this->schemaBreadcrumb)) {
			add_filter(
				'wpseo_schema_breadcrumb',
				$this->schemaBreadcrumb,
				$this->schemaBreadcrumbPriority,
				$this->schemaBreadcrumbAcceptedArgs
			);
		}
	}

	public function schemaWebpage(): void {
		if (is_callable($this->schemaWebpage)) {
			add_filter(
				'wpseo_schema_webpage',
				$this->schemaWebpage,
				$this->schemaWebpagePriority,
				$this->schemaWebpageAcceptedArgs
			);
		}
	}

	public function breadcrumbLinks(): void {
		add_filter('wpseo_breadcrumb_links',
			$this->breadcrumbLinks,
			$this->breadcrumbLinksPriority,
			$this->breadcrumbLinksAcceptedArgs
		);
	}

	public function breadcrumbSingleLink(): void {
		add_filter('wpseo_breadcrumb_single_link',
			$this->breadcrumbSingleLink,
			$this->breadcrumbSingleLinkPriority,
			$this->breadcrumbSingleLinkAcceptedArgs
		);
	}

	/*
	 *
	 */

	public function setBreadcrumbLinks($breadcrumbLinks, $priority = 10, $accepted_args = 0): void {
		$this->breadcrumbLinks             = $breadcrumbLinks;
		$this->breadcrumbLinksPriority     = $priority;
		$this->breadcrumbLinksAcceptedArgs = $accepted_args;
	}

	public function getBreadcrumbLinks() {
		return $this->breadcrumbLinks;
	}

	public function setBreadcrumbSingleLink($breadcrumbSingleLink, $priority = 10, $accepted_args = 0): void {
		$this->breadcrumbSingleLink             = $breadcrumbSingleLink;
		$this->breadcrumbSingleLinkPriority     = $priority;
		$this->breadcrumbSingleLinkAcceptedArgs = $accepted_args;
	}

	public function getBreadcrumbSingleLink() {
		return $this->breadcrumbSingleLink;
	}

	/*
	 *
	 */

	public function setRobots($robots): void {
		$this->robots = $robots;
	}

	public function getRobots(): mixed {
		return $this->robots;
	}

	public function setCanonical($canonical): void {
		$this->canonical = $canonical;
	}

	public function getCanonical(): mixed {
		return $this->canonical;
	}


	/*
	 *
	 */

	public function setTitle($title): void {
		$this->title = $title;
	}

	public function setDocumentTitle($title): void {
		add_filter('pre_get_document_title', function($title) {
			return $this->title;
		});
	}

	public function getTitle(): mixed {
		return $this->title;
	}

	public function setDescription($description): void {
		$this->description = $description;
	}

	public function getDescription(): mixed {
		return $this->description;
	}

	public function setOpengraphTitle($opengraphTitle): void {
		$this->opengraphTitle = $opengraphTitle;
	}

	public function getOpengraphTitle(): mixed {
		return $this->opengraphTitle;
	}

	public function setOpengraphDescription($opengraphDescription): void {
		$this->opengraphDescription = $opengraphDescription;
	}

	public function getOpengraphDescription(): mixed {
		return $this->opengraphDescription;
	}

	public function setOpengraphURL($opengraphURL): void {
		$this->opengraphURL = $opengraphURL;
	}

	public function getOpengraphURL(): mixed {
		return $this->opengraphURL;
	}

	/*
	 *
	 */

	public function setSchema($schema, $priority = 10, $accepted_args = 0): void {
		$this->schema             = $schema;
		$this->schemaPriority     = $priority;
		$this->schemaAcceptedArgs = $accepted_args;
	}

	public function getSchema(): mixed {
		return $this->schema;
	}

	public function setSchemaWebpage($schemaWebpage, $priority = 10, $accepted_args = 0): void {
		$this->schemaWebpage             = $schemaWebpage;
		$this->schemaWebpagePriority     = $priority;
		$this->schemaWebpageAcceptedArgs = $accepted_args;
	}

	public function getSchemaWebpage(): mixed {
		return $this->schemaWebpage;
	}

	public function setSchemaBreadcrumb($schemaBreadcrumb, $priority = 10, $accepted_args = 0): void {
		$this->schemaBreadcrumb             = $schemaBreadcrumb;
		$this->schemaBreadcrumbPriority     = $priority;
		$this->schemaBreadcrumbAcceptedArgs = $accepted_args;
	}

	public function getSchemaBreadcrumb(): mixed {
		return $this->schemaBreadcrumb;
	}

}