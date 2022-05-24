# Documentation knight-artifact-entity-adapter-arangodb

Knight PHP library to build query in ArangoDB.

**NOTE:** This repository is part of [Knight](https://github.com/energia-source/knight). Any
support requests, bug reports, or development contributions should be directed to
that project.

## Installation

To begin, install the preferred dependency manager for PHP, [Composer](https://getcomposer.org/).

Now to install just this component:

```sh

$ composer require knight/knight-artifact-entity-adapter-arangodb

```

## Configuration

Configuration are grouped into configuration namespace by the framework [Knight](https://github.com/energia-source/knight).
The configuration files are stored in the configurations folder and in the file named ArangoDB.php or Dielect.php that you have previously imported.

So the basic setup looks something like this:

```

<?PHP

namespace configurations;

use Knight\Lock;

use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\UpdatePolicy;

final class ArangoDB
{
	use Lock;

	const PARAMETERS = [
		// database name
		ConnectionOptions::OPTION_DATABASE => 'database',
		// server endpoint to connect
		ConnectionOptions::OPTION_ENDPOINT => 'https://10.172.10.172:8529/',
		// authorization type to use (currently supported: 'Basic')
		ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
		// user for basic authorization
		ConnectionOptions::OPTION_AUTH_USER => 'username',
		// password for basic authorization
		ConnectionOptions::OPTION_AUTH_PASSWD => 'password',
		// connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
		ConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
		// connect timeout in seconds
		ConnectionOptions::OPTION_TIMEOUT => 8,
		// whether or not to reconnect when a keep-alive connection has timed out on server
		ConnectionOptions::OPTION_RECONNECT => false,
		// optionally create new collections when inserting documents
		ConnectionOptions::OPTION_CREATE => false,
		// optionally create new collections when inserting documents
		ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
	];
}

```

## Structure

library:
- [ArangoDB\adapters\map](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/adapters/map)
- [ArangoDB\adapters](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/adapters)
- [ArangoDB\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/common)
- [ArangoDB\entity\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/entity/common)
- [ArangoDB\entity](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/entity)
- [ArangoDB\operations\features](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/features)
- [ArangoDB\operations\common\base](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/base)
- [ArangoDB\operations\common\choose\strict](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/choose/strict)
- [ArangoDB\operations\common\choose](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/choose)
- [ArangoDB\operations\common\handling](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/handling)
- [ArangoDB\operations\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common)
- [ArangoDB\operations](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations)
- [ArangoDB\parser](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/parser)
- [ArangoDB](https://github.com/energia-source/knight-knight-artifact-entity-adapter-arangodb/blob/main/lib)

<br>

## ***Prerequisites***

questa libreria deve dipendere da l'orm knight-entity.
Servwe per creare ed eseguire in modo facile e veloce complessi attraversamenti nel grafo di arangoDB.
I metodi principali per disegnare lato PHP il grafo sono edge->vertex() e vertex->useEdge()

This library is related to knight-enitity.
It is used to create and execute in an easy way complex route in a ArangoDB graph.
The main methods to create a graph in PHP are edge->vertex() and vertex->useEdge().


```

<?PHP

use use ArangoDB\entity\Vertex as Extendible;

class Vertex extends Extendible
{
	const COLLECTION = 'User';

	protected function initialize() : void
	{
		$key = $this->getField('key');
		$key_validator = Validation::factory('Regex');
		$key_validator->setRegex('/\d+/');
		$key->setPatterns($key_validator);
    }
}

```

```

<?PHP
namespace applications\iam\user\database\edges;

use ArangoDB\entity\Edge;

class UserToUser extends Edge
{
	const TARGET = 'applications\\iam\\user\\database';
	const COLLECTION = 'UserToUser';
	const DIRECTION = Edge::OUTBOUND;
}

```


## ***Perform create query***

<br>

Usually to create a vertex connected to another, we need to create the edge to connect the new document to another vertex.


the  The edge designed automatically create by the library.
So the basic create document into collection and related collection usage looks something like this:

<br>

```

<?PHP

namespace what\you\want;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Request;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\Vertex;
use ArangoDB\operations\common\Handling;

use applications\customer\contact\database\Vertex as Contact;
use applications\customer\contact\database\edges\ContactToUser;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/what/you/want/action/create')) Output::print(false);

$upsert = new Contact();
$upsert->setFromAssociative((array)Request::post());

if (!!$errors = $upsert->checkRequired(true)->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$contact_query = ArangoDB::start($contact);

// Add edge from vertex created and my vertex identity

$user = $contact->useEdge(ContactToUser::getName())->vertex();
$user->getField(Sso::IDENTITY)->setSafeModeDetached(false)->setValue(Sso::getWhoamiKey());

$contact_query_insert = $contact_query->insert();
$contact_query_insert->pushEntitySkips($user);
$contact_query_insert->setActionOnlyEdges(false);
$contact_query_insert->pushTransactionsPreliminary($user_query_upsert->getTransaction());
$contact_query_insert_return = 'RETURN' . chr(32) . Handling::RNEW;
$contact_query_insert->getReturn()->setPlain($contact_query_insert_return);
$contact_query_insert->setEntityEnableReturns($contact);

foreach (Vertex::MANAGEMENT as $name)
    $contact->getField($name)->setProtected(false)->setRequired(true)->setValue(Sso::getWhoamiKey());

$contact_query_insert_response = $contact_query_insert->run();
if (null === $contact_query_insert_response) Output::print(false);

Output::concatenate(Output::APIDATA, reset($contact_query_insert_response));
Output::print(true);

```

<br>

## ***Perform select query***

<br>

In this example case we perform a select with a traversal edge for find a specific vertex is taken and inserted as an array in the result of the main query.
So the basic example you get all user on your hierarchy on graph database.

<br>

```

<?PHP

namespace what\you\want;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\operations\common\Choose;

use applications\iam\user\database\Vertex as User;
use applications\iam\user\database\edges\UserToUser;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/what/you/want/action/read')) Output::print(false);

$user = User::login();
$user_query = ArangoDB::start($user);

$edge = $user->useEdge(UserToUser::getName());

$post = Request::post();
$post = array_filter((array)$post, function ($item) {
    return !is_string($item) && !is_numeric($item)
        || strlen((string)$item);
});

$user = $edge->vertex();
$user->setSafeMode(false);
$user_fields = $user->getFields();
foreach ($user_fields as $field) {
    $field_name = $field->getName();
    if (false === array_key_exists($field_name, $post)
        || $field->getProtected()) continue;

    $user->getField($field_name)->setValue($post[$field_name]);
}

$user->useEdge(UserToUser::getName(), $edge)->vertex();

// Permit traversal throwth user->user->user deep

$user->useEdge(UserToUser::getName())->vertex()->useEdge(UserToUser::getName(), $edge)->vertex();

$user_query_select = $user_query->select();
$user_query_select_limit = $user_query_select->getLimit();
if (!!$count_offset = Request::get('offset')) $user_query_select_limit->setOffset($count_offset);
if (!!$count = Request::get('count')) $user_query_select_limit->set($count);

$user_query_select_return = 'RETURN' . chr(32) . $user_query_select->getPointer(Choose::VERTEX);
$user_query_select->getReturn()->setPlain($user_query_select_return);
$user_query_select_response = $user_query_select->run();
if (null === $user_query_select_response) Output::print(false);

Output::concatenate(Output::APIDATA, array_filter($user_query_select_response));
Output::print(true);

```

<br>

## ***Perform update query***

<br>

In this case the syntax modifies the document in the collection, id you want you can change all traversal associaton to thir document.
So the basic update document and check by statement inside a transaction the cluster document is direct connected to me:

<br>

```

<?PHP

namespace what\you\want;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Request;
use Knight\armor\Language;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Handling;

use applications\iam\user\database\Vertex as User;
use applications\iam\user\database\edges\UserToCluster;
use applications\sso\cluster\database\Vertex as Cluster;
use applications\sso\cluster\database\edges\ClusterToUser;

use extensions\Navigator;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/what/you/want/action/read')) Output::print(false);

$cluster_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$cluster_key_value = basename($cluster_key_value);

$user = User::Login();
$user_query = ArangoDB::start($user);

$cluster = $user->useEdge(UserToCluster::getName())->vertex();
$cluster->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($cluster_key_value);

// Check if cluster is direct connected on my document

$cluster_query = ArangoDB::start($cluster);
$cluster->useEdge(ClusterToUser::getName())->vertex($user);
$cluster_select = $cluster_query->select();
$cluster_select->getLimit()->set(1);
$cluster_select_return = 'RETURN 1';
$cluster_select->getReturn()->setPlain($cluster_select_return);
$cluster_select_statement = $cluster_select->getStatement();
$cluster_select_statement->setExpect(1)->setHideResponse(true);
$cluster->getContainer()->removeEdgesByName(ClusterToUser::getName());

$cluster->setFromAssociative((array)Request::post());
$cluster->getField(Arango::KEY)->setValue($cluster_key_value);

$cluster_query_update = $cluster_query->update();
$cluster_query_update->setReplace(true);
$cluster_query_update_return = 'RETURN' . chr(32) . Handling::RNEW;
$cluster_query_update->getReturn()->setPlain($cluster_query_update_return);
$cluster_query_update->setEntityEnableReturns($cluster);

if (!!$errors = $cluster->checkRequired(true)->getAllFieldsWarning()) {
	Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

$cluster_fields = $cluster->getFields();
foreach ($cluster_fields as $field) $field->setRequired(true);

$cluster_query_update->pushStatementsPreliminary($cluster_select_statement);
$cluster_query_update_response = $cluster_query_update->run();
if (null === $cluster_query_update_response
    || empty($cluster_query_update_response)) Output::print(false);

$cluster = new Cluster();
$cluster->setSafeMode(false)->setReadMode(true);
$cluster_value = reset($cluster_query_update_response);
$cluster->setFromAssociative($cluster_value, $cluster_value);
$cluster_value = $cluster->getAllFieldsValues(false, false);

Output::concatenate(Output::APIDATA, $cluster_value);
Output::print(true);

```

<br>

## ***Perform delete query***

<br>

Delete document and all edges inbound and all edge outbound where directed connected to us.
So the basic delete record usage looks something like this:

**NOTE:** If you dont't specify flag ActionOnlyEdges to false the library make a query for delete only edge by designed in your graph query. 

<br>

```

<?PHP

namespace what\you\want;

use IAM\Sso;
use IAM\Configuration as IAMConfiguration;

use Knight\armor\Output;
use Knight\armor\Language;
use Knight\armor\Navigator;

use ArangoDB\Initiator as ArangoDB;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Handling;

use applications\customer\contact\database\Vertex as Contact;

$application_basename = IAMConfiguration::getApplicationBasename();
if (Sso::youHaveNoPolicies($application_basename . '/what/you/want/action/delete')) Output::print(false);

$follow = new Contact();
ArangoDB::start($follow);
$follow = $follow->getAllUsableEdgesName(true);

$contact_field_key_value = parse_url($_SERVER[Navigator::REQUEST_URI], PHP_URL_PATH);
$contact_field_key_value = basename($contact_field_key_value);

$contact = new Contact();
$contact_fields = $contact->getFields();
foreach ($contact_fields as $field) $field->setProtected(true);

$contact->getField(Arango::KEY)->setProtected(false)->setRequired(true)->setValue($contact_field_key_value);

if (!!$errors = $contact->checkRequired()->getAllFieldsWarning()) {
    Language::dictionary(__file__);
    $notice = __namespace__ . '\\' . 'notice';
    $notice = Language::translate($notice);
    Output::concatenate('notice', $notice);
    Output::concatenate('errors', $errors);
    Output::print(false);
}

// First delete all inbound and all outbound edge from this vertex

$contact_query = ArangoDB::start($contact);
foreach ($follow as $name) {
    $contact->useEdge($name)->setForceDirection(Edge::INBOUND);
    $contact->useEdge($name)->setForceDirection(Edge::OUTBOUND);
}

$contact_query_delete = $contact_query->remove();
$contact_query_delete->setActionOnlyEdges(false);
$contact_query_delete_return = 'RETURN' . chr(32) . Handling::ROLD;
$contact_query_delete->getReturn()->setPlain($contact_query_delete_return);
$contact_query_delete->setEntityEnableReturns($contact);
$contact_query_delete_response = $contact_query_delete->run();
if (null === $contact_query_delete_response) Output::print(false);

Output::concatenate(Output::APIDATA, reset($contact_query_delete_response));
Output::print(true);

```

<br>

## Built With

* [PHP](https://www.php.net/) - PHP

## Contributing

Please read [CONTRIBUTING.md](https://github.com/energia-source/knight-knight-artifact-entity-adapter-arangodb/blob/main/CONTRIBUTING.md) for details on our code of conduct, and the process for submitting us pull requests.

## Versioning

We use [SemVer](https://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/energia-source/knight-knight-artifact-entity-adapter-arangodb/tags). 

## Authors

* **Paolo Fabris** - *Initial work* - [energia-europa.com](https://www.energia-europa.com/)
* **Gabriele Luigi Masero** - *Developer* - [energia-europa.com](https://www.energia-europa.com/)

See also the list of [contributors](https://github.com/energia-source/knight-knight-artifact-entity-adapter-arangodb/blob/main/CONTRIBUTORS.md) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
