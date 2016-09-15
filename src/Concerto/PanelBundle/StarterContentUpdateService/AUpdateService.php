<?php

namespace Concerto\PanelBundle\StarterContentUpdateService;

abstract class AUpdateService {

    protected $update_history = array(
    );

    public function update($user, $service, $new_ent, $old_ent) {
        $updated = false;
        $pre = $new_ent->isStarterContent() && $old_ent->isStarterContent() && $new_ent->getRevision() > 0 && $old_ent->getRevision() > 0 && $new_ent->getRevision() > $old_ent->getRevision();
        if (!$pre)
            return $updated;

        foreach ($this->update_history as $uh) {
            if ($old_ent->getName() == $uh["name"] && $old_ent->getRevision() < $uh["rev"] && $new_ent->getRevision() >= $uh["rev"]) {
                call_user_method_array($uh["func"], $this, array($user, $service, $new_ent, $old_ent));
                $updated = true;
            }
        }
        return $updated;
    }

}
