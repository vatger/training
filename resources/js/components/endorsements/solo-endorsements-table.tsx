import { getPositionIcon, getStatusBadge } from "@/pages/endorsements/trainee";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../ui/table";
import { Clock } from "lucide-react";
import { Endorsement } from "@/types";

export default function SoloEndorsementsTable({ endorsements }: { endorsements: Endorsement[] }) {
  return (
      <div className="rounded-md border">
          <Table>
              <TableHeader>
                  <TableRow>
                      <TableHead>Position</TableHead>
                      <TableHead>Mentor</TableHead>
                      <TableHead>Expires</TableHead>
                      <TableHead>Status</TableHead>
                  </TableRow>
              </TableHeader>
              <TableBody>
                  {endorsements.map((endorsement) => (
                      <TableRow key={endorsement.position} className="h-18">
                          <TableCell>
                              <div className="flex items-center gap-3">
                                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                      {getPositionIcon(endorsement.type)}
                                  </div>
                                  <div>
                                      <div className="font-medium">{endorsement.position}</div>
                                      <div className="text-sm text-muted-foreground">{endorsement.fullName}</div>
                                  </div>
                              </div>
                          </TableCell>
                          <TableCell>
                              <div>
                                  <div className="font-medium">{endorsement.mentor}</div>
                                  <div className="text-sm text-muted-foreground">ID: 1234567</div>
                              </div>
                          </TableCell>
                          <TableCell>
                              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                  <Clock className="h-4 w-4" />
                                  {new Date(endorsement.expiresAt!).toLocaleDateString("de")}
                              </div>
                          </TableCell>
                          <TableCell>{getStatusBadge(endorsement.status)}</TableCell>
                      </TableRow>
                  ))}
              </TableBody>
          </Table>
      </div>
  );
}