<?PHP

namespace ArangoDB\operations\common\choose;
/* The Limit class is used to set a limit and an offset for a query */

class Limit
{
    protected $limit;  // (int)
    protected $offset; // (int)

   /**
    * *This function sets the limit of the query.*
    * 
    * The next step is to add the limit to the query
    * 
    * @param int limit The number of results to return.
    * 
    * @return The object itself.
    */
   
    public function set(int $limit) : self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get the limit value
     * 
     * @return The limit value.
     */
    
    public function get() :? int
    {
        return $this->limit;
    }

    /**
     * Set the offset for the next request
     * 
     * @param int offset The number of records to skip.
     * 
     * @return The object itself.
     */
    
    public function setOffset(int $offset) : self
    {
        $offset = abs($offset);
        $status = $this->get();
        if (null === $status) $this->set($offset);

        $this->offset = $offset;
        return $this;
    }

    /**
     * Returns the offset of the current row
     * 
     * @return The offset value.
     */
    
    public function getOffset() :? int
    {
        return $this->offset;
    }
}