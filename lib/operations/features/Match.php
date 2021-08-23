<?PHP

namespace ArangoDB\operations\features;

use Entity\Map as Entity;

use ArangoDB\Statement;
use ArangoDB\operations\common\Document;

trait Match
{
    protected $or = []; // (array) Entity

    protected function matches(string $name, Statement $statement, Document ...$documents) : array
    {
        $response = [];
        $document_match = array_map(function (Document $document) {
            return clone $document;
        }, $documents);
        $like = array_map(function (Document $document) {
            return $document->getValues();
        }, $document_match);
        $like = serialize($like);
        $like = strpos($like, chr(37));
        if (false === $like) {
            $bind_documents = $statement->bindDocuments(true, ...$document_match);
            if (null !== $bind_documents) {
                $matches = 'MATCHES' . chr(40) . $name . chr(44) . chr(32) . $bind_documents . chr(41);
                array_push($response, $matches);
                return $response;
            }
        }

        $or = $this->getEntitiesUsingOr();
        $or = array_map(function (Entity $entity) {
            return $entity->getHash();
        }, $or);
        foreach ($document_match as $document) {
            $conditions = [];
            if ($grep = preg_grep('/%/', $document->getValues())) {
                $grep_bind = $statement->bind($grep, true);
                foreach ($grep_bind as $key => $bind) {
                    $like = 'LIKE' . chr(40) . $name . chr(46) . $key . chr(44) . chr(32) . $bind . chr(44) . chr(32) . 'true' . chr(41);
                    array_push($conditions, $like);
                    $document->unsetFields($key);
                }
            }

            if ($document->getValues()) {
                $document_bind = $statement->bindDocument(true, $document);
                if (null !== $document_metadata_bind) {
                    $matches = 'MATCHES' . chr(40) . $name . chr(44) . chr(32) . $document_bind . chr(41);
                    array_push($conditions, $matches);
                }
            }

            if (empty($conditions)) continue;

            $conditions_operator = in_array($document->getEntity()->getHash(), $or) ? 'OR' : 'AND';
            $conditions_operator = chr(32) . $conditions_operator . chr(32);
            $conditions = count($conditions) > 1 ? implode($conditions_operator, $conditions) : reset($conditions);
            $conditions = chr(40) . $conditions . chr(41);
            array_push($response, $conditions);
        }
        return array_filter($response);
    }

    public function pushEntitiesUsingOr(Entity ...$or) : int
    {
        return array_push($this->or, ...$or);
    }

    protected function getEntitiesUsingOr() : array
    {
        return $this->or;
    }
}