This module allows processing of long sequences of operations that may run longer than the PHP timeout limit, while providing feedback to the user about the progression of the sequence execution on the server. It may be useful for admin tasks like importing large quantities of data into a database, setting up a CMS, analyzing large files submitted by users...

In the scope of this module, a long sequence of operations is called a "batch".

Batches are created by extending the Batch class and defining a start() method. The start method is the first operation that will be executed in the batch. Each operation is required to return the method name and parameters of the next one. The batch finishes when an operation returns null. It doesn't matter how long the whole batch runs, but individual operations must never run longer than half the PHP timeout limit.

Batches are executed by instanciating your Batch class extension and simply echoing it. This will include the required html and javascript in the page. You can customize the generated html and javascript by copying the batch view from the modules/batch/views directory to your application/views directory and modifying it there. When the page is rendered in a web browser with javascript enabled, the batch processing will start on the server.

Data (feedback messages most likely but it can be any php object) can be sent from the server to the client by calling the post($someobject) method in your batch class, or the helper method message($somemessage, $somepercentage). Data posted this way will show up in the client in the _batch_message($someobject) js function that will be called automatically when a new message from the server is available. Data (cancel messages most likely, it can only be strings) can be sent from the client to the server by calling batch_post(somestring) in js. Data posted this way can be accessed from the server by calling the read() method in your batch class.

Jquery is required. Two tables are required (see schema.sql in the module directory).

As for how it works. The processing of the batch is spread over as many subsequent ajax requests as required to finish it. Between each request, the batch object is serialized and stored. This is totally transparent to the programmer of the batch.

For further details, see the code and examples, I tried my best to properly comment it.

To test the module, uncomment the Controller_Batch::action_test() method and point your browser to the /batch/test URL.

Files included :
- classes/batch.php : the base Batch class,
- classes/batch/test.php : a sample batch class,
- classes/controller/batch.php : controller to handle ajax requests,
- views/batch.php : the default batch view,
- media/js/batch.js : required js file (will be included automatically)
- schema.sql : required tables