<?php

declare(strict_types=1);

namespace Dbapi\Forms\Infrastructure\Module;

use Dbapi\Shared\Infrastructure\Module\ModuleProvisioner;
use Illuminate\Database\Schema\Blueprint;

final class FormsModuleProvisioner extends ModuleProvisioner
{
    public function moduleName(): string
    {
        return 'forms';
    }

    public function provision(string $appId): void
    {
        $this->createIfMissing("{$appId}_forms", function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('recipient_email')->nullable();
            $table->json('fields');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $this->createIfMissing("{$appId}_form_submissions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->json('data');
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('form_id')->references('id')->on("{$appId}_forms")->cascadeOnDelete();
            $table->index('form_id');
        });
    }

    public function deprovision(string $appId): void
    {
        $this->dropIfExists("{$appId}_form_submissions");
        $this->dropIfExists("{$appId}_forms");
    }
}
