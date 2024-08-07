<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	private const TABLE_NAME = 'configs';

	public function up(): void
	{
		if (Schema::hasTable(self::TABLE_NAME)) {
			// Nothing to do. Bye.
			return;
		}
		Schema::create(self::TABLE_NAME, function (Blueprint $table) {
			$table->increments('id');
			$table->string('key', 50);
			$table->string('value', 200)->nullable();
			$table->string('cat', 50)->default('Config');
			$table->string('type_range')->default('0|1');
			$table->boolean('is_secret')->default(false);
			$table->string('description')->default('');
		});
	}

	public function down(): void
	{
		// do nothing
	}
};
