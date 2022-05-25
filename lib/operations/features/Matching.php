<?PHP

namespace ArangoDB\operations\features;

use Entity\Map as Entity;

use ArangoDB\Statement;
use ArangoDB\operations\common\base\Document;

/* The `trait` keyword is used to create a trait. A trait is a collection of methods that can be used
as if they were part of the class in which they are declared. */

trait Matching
{
    protected $or = []; // (array) Entity

    /**
     * It matches the documents to the statement.
     * 
     * @param string name The name of the field to match.
     * @param Statement statement The statement to bind the documents to.
     * 
     * @return The `matches()` method returns an array of conditions that will be used to build the
     * query.
     */
    
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

    /**
     * *This function adds one or more entities to the or array.*
     * 
     * @return The object itself.
     */
    
    public function pushEntitiesUsingOr(Entity ...$or) : self
    {
        array_push($this->or, ...$or);
        return $this;
    }

    /**
     * This function returns an array of entities that are used in the or clause
     * 
     * @return An array of entities that are being used in the query.
     */
    
    protected function getEntitiesUsingOr() : array
    {
        return $this->or;
    }
}
