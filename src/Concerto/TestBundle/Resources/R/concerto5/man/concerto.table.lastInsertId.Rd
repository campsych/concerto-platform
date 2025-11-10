\name{concerto.table.lastInsertId}
\alias{concerto.table.lastInsertId}
\title{
    Last insert id
}
\description{
    Gets last inserted record id
}
\usage{
    concerto.table.lastInsertId(connection)
}
\arguments{
    \item{connection}{
        Connection to use.
    }
    \item{tableName}{
        Required explicit table name value for some db drivers.
    }
}
\details{
}
\value{
    Last insert id
}
\references{
}
\author{
    Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
    lid <- concerto.table.lastInsertId()
}

\keyword{table}
\keyword{database}
